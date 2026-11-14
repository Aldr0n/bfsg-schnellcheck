<?php

namespace App\Services;

use App\Enums\AccessibilityRule;
use App\Models\Finding;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AccessibilityCheckService
{
    private const MAX_STORED_FINDINGS_PER_RULE = 150;

    private const MAX_SNIPPET_LENGTH = 300;

    private const INPUT_TYPES_WITHOUT_LABEL = ['hidden', 'submit', 'reset', 'button'];

    /**
     * Parses the HTML once and runs every rule on it. The findings are unsaved and capped per rule.
     * The counts are keyed by the rule value: the checked counts count the checked elements,
     * the finding counts also include the findings beyond the cap.
     * Language and title are checked once per page.
     *
     * @return array{findings: array<int, Finding>, checkedCounts: array<string, int>, findingCounts: array<string, int>}
     */
    public function checkHtml(string $html): array
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        $results = collect(AccessibilityRule::cases())
            ->mapWithKeys(fn (AccessibilityRule $rule) => [
                $rule->value => $this->checkRule($rule, $document),
            ]);

        return [
            'findings' => $results->flatMap(fn (array $result) => $result['findings'])->all(),
            'checkedCounts' => $results->map(fn (array $result) => $result['checkedCount'])->all(),
            'findingCounts' => $results->map(fn (array $result) => $result['findingCount'])->all(),
        ];
    }

    /**
     * @return array{checkedCount: int, findingCount: int, findings: array<int, Finding>}
     */
    private function checkRule(AccessibilityRule $rule, HTMLDocument $document): array
    {
        $result = match ($rule) {
            AccessibilityRule::ImageAlt => $this->checkImages($document),
            AccessibilityRule::DocumentLanguage => $this->checkDocumentLanguage($document),
            AccessibilityRule::DocumentTitle => $this->checkDocumentTitle($document),
            AccessibilityRule::FormLabel => $this->checkFormFields($document),
            AccessibilityRule::LinkText => $this->checkLinks($document),
        };

        $findings = $result['violatingElements']
            ->take(self::MAX_STORED_FINDINGS_PER_RULE)
            ->map(fn (Element $element) => $this->makeFinding($rule, $document, $element))
            ->all();

        return [
            'checkedCount' => $result['checkedCount'],
            'findingCount' => $result['violatingElements']->count(),
            'findings' => $findings,
        ];
    }

    /**
     * Images need an alt attribute, except the ones hidden from assistive technology.
     *
     * @return array{checkedCount: int, violatingElements: Collection<int, Element>}
     */
    private function checkImages(HTMLDocument $document): array
    {
        $images = collect($document->querySelectorAll('img:not([aria-hidden="true"]):not([role="presentation"]):not([role="none"])'));

        return [
            'checkedCount' => $images->count(),
            'violatingElements' => $images->reject(fn (Element $image) => $image->hasAttribute('alt')),
        ];
    }

    /**
     * @return array{checkedCount: int, violatingElements: Collection<int, Element>}
     */
    private function checkDocumentLanguage(HTMLDocument $document): array
    {
        $root = $document->documentElement;

        return [
            'checkedCount' => 1,
            'violatingElements' => $this->hasFilledAttribute($root, 'lang') ? collect() : collect([$root]),
        ];
    }

    /**
     * @return array{checkedCount: int, violatingElements: Collection<int, Element>}
     */
    private function checkDocumentTitle(HTMLDocument $document): array
    {
        return [
            'checkedCount' => 1,
            'violatingElements' => $document->title !== '' ? collect() : collect([$document->head]),
        ];
    }

    /**
     * @return array{checkedCount: int, violatingElements: Collection<int, Element>}
     */
    private function checkFormFields(HTMLDocument $document): array
    {
        $labelledIds = collect($document->querySelectorAll('label[for]'))->map(fn (Element $label) => $label->getAttribute('for'));

        $fields = collect($document->querySelectorAll('input, select, textarea'))
            ->reject(fn (Element $field) => $this->isButtonOrHiddenInput($field));

        return [
            'checkedCount' => $fields->count(),
            'violatingElements' => $fields->reject(fn (Element $field) => $this->hasFieldAccessibleName($field, $labelledIds)),
        ];
    }

    /**
     * Buttons and hidden inputs need no label.
     */
    private function isButtonOrHiddenInput(Element $field): bool
    {
        return $field->tagName === 'INPUT'
            && in_array(strtolower($field->getAttribute('type') ?? ''), self::INPUT_TYPES_WITHOUT_LABEL, true);
    }

    /**
     * @param  Collection<int, string|null>  $labelledIds  The ids that a label points to.
     */
    private function hasFieldAccessibleName(Element $field, Collection $labelledIds): bool
    {
        $type = strtolower($field->getAttribute('type') ?? '');

        return $this->hasFilledAttribute($field, 'aria-label')
            || $this->hasFilledAttribute($field, 'aria-labelledby')
            || ($type === 'image' && $this->hasFilledAttribute($field, 'alt'))
            || $field->closest('label') !== null
            || $labelledIds->containsStrict($field->getAttribute('id'));
    }

    /**
     * @return array{checkedCount: int, violatingElements: Collection<int, Element>}
     */
    private function checkLinks(HTMLDocument $document): array
    {
        $links = collect($document->querySelectorAll('a[href]'));

        return [
            'checkedCount' => $links->count(),
            'violatingElements' => $links->reject(fn (Element $link) => $this->hasLinkAccessibleName($link)),
        ];
    }

    private function hasLinkAccessibleName(Element $link): bool
    {
        return filled($link->textContent)
            || $this->hasFilledAttribute($link, 'aria-label')
            || $this->hasFilledAttribute($link, 'aria-labelledby')
            || $this->hasFilledAttribute($link, 'title')
            || $this->containsNamedDescendant($link);
    }

    /**
     * Whether a descendant is an image with alt text or carries an aria-label, like an icon.
     */
    private function containsNamedDescendant(Element $element): bool
    {
        return collect($element->querySelectorAll('img[alt], [aria-label]'))
            ->contains(fn (Element $descendant) => $this->hasFilledAttribute($descendant, 'alt')
                || $this->hasFilledAttribute($descendant, 'aria-label'));
    }

    private function hasFilledAttribute(Element $element, string $attribute): bool
    {
        return filled($element->getAttribute($attribute));
    }

    /**
     * The tag without its children, because a missing attribute belongs to the tag and not to its content.
     */
    private function openingTag(Element $element): string
    {
        $tag = '<'.strtolower($element->tagName);

        foreach ($element->attributes as $attribute) {
            $tag .= ' '.$attribute->name.'="'.htmlspecialchars($attribute->value).'"';
        }

        return $tag.'>';
    }

    /**
     * The snippet is the whole element where its content matters, otherwise only the tag.
     */
    private function makeFinding(AccessibilityRule $rule, HTMLDocument $document, Element $element): Finding
    {
        $html = match ($rule) {
            AccessibilityRule::ImageAlt, AccessibilityRule::LinkText => $document->saveHtml($element),
            AccessibilityRule::DocumentLanguage, AccessibilityRule::DocumentTitle, AccessibilityRule::FormLabel => $this->openingTag($element),
        };

        return new Finding(['accessibility_rule' => $rule, 'snippet' => Str::limit(Str::squish($html), self::MAX_SNIPPET_LENGTH)]);
    }
}
