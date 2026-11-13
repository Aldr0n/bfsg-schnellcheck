<?php

namespace App\Services;

use App\Enums\AccessibilityRule;
use App\Models\Finding;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Str;

class AccessibilityCheckService
{
    private const MAX_SNIPPET_LENGTH = 300;

    private const INPUT_TYPES_WITHOUT_LABEL = ['hidden', 'submit', 'reset', 'button'];

    /**
     * Parses the HTML once and runs every rule on it. The findings are unsaved, the checked counts are keyed by the rule value.
     * Language and title are checked once per page.
     *
     * @return array{findings: array<int, Finding>, checkedCounts: array<string, int>}
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
        ];
    }

    /**
     * @return array{checkedCount: int, findings: list<Finding>}
     */
    private function checkRule(AccessibilityRule $rule, HTMLDocument $document): array
    {
        return match ($rule) {
            AccessibilityRule::ImageAlt => $this->checkImages($document),
            AccessibilityRule::DocumentLanguage => $this->checkDocumentLanguage($document),
            AccessibilityRule::DocumentTitle => $this->checkDocumentTitle($document),
            AccessibilityRule::FormLabel => $this->checkFormFields($document),
            AccessibilityRule::LinkText => $this->checkLinks($document),
        };
    }

    /**
     * Images need an alt attribute, except the ones hidden from assistive technology.
     *
     * @return array{checkedCount: int, findings: list<Finding>}
     */
    private function checkImages(HTMLDocument $document): array
    {
        $images = $document->querySelectorAll('img:not([aria-hidden="true"]):not([role="presentation"]):not([role="none"])');

        $findings = [];

        foreach ($images as $image) {
            if (! $image->hasAttribute('alt')) {
                $findings[] = $this->makeFinding(AccessibilityRule::ImageAlt, $document->saveHtml($image));
            }
        }

        return ['checkedCount' => count($images), 'findings' => $findings];
    }

    /**
     * @return array{checkedCount: int, findings: list<Finding>}
     */
    private function checkDocumentLanguage(HTMLDocument $document): array
    {
        $root = $document->documentElement;

        if ($this->hasFilledAttribute($root, 'lang')) {
            return ['checkedCount' => 1, 'findings' => []];
        }

        return ['checkedCount' => 1, 'findings' => [$this->makeFinding(AccessibilityRule::DocumentLanguage, $this->openingTag($root))]];
    }

    /**
     * @return array{checkedCount: int, findings: list<Finding>}
     */
    private function checkDocumentTitle(HTMLDocument $document): array
    {
        if ($document->title !== '') {
            return ['checkedCount' => 1, 'findings' => []];
        }

        return ['checkedCount' => 1, 'findings' => [$this->makeFinding(AccessibilityRule::DocumentTitle, $this->openingTag($document->head))]];
    }

    /**
     * @return array{checkedCount: int, findings: list<Finding>}
     */
    private function checkFormFields(HTMLDocument $document): array
    {
        $labelledIds = collect($document->querySelectorAll('label[for]'))->map(fn (Element $label) => $label->getAttribute('for'));

        $checkedCount = 0;
        $findings = [];

        foreach ($document->querySelectorAll('input, select, textarea') as $field) {
            $type = strtolower($field->getAttribute('type') ?? '');

            if ($field->tagName === 'INPUT' && in_array($type, self::INPUT_TYPES_WITHOUT_LABEL, true)) {
                continue;
            }

            $checkedCount++;

            $hasAccessibleName = $this->hasFilledAttribute($field, 'aria-label')
                || $this->hasFilledAttribute($field, 'aria-labelledby')
                || ($type === 'image' && $this->hasFilledAttribute($field, 'alt'))
                || $field->closest('label') !== null
                || $labelledIds->containsStrict($field->getAttribute('id'));

            if (! $hasAccessibleName) {
                $findings[] = $this->makeFinding(AccessibilityRule::FormLabel, $this->openingTag($field));
            }
        }

        return ['checkedCount' => $checkedCount, 'findings' => $findings];
    }

    /**
     * @return array{checkedCount: int, findings: list<Finding>}
     */
    private function checkLinks(HTMLDocument $document): array
    {
        $links = $document->querySelectorAll('a[href]');

        $findings = [];

        foreach ($links as $link) {
            $hasAccessibleName = filled($link->textContent)
                || $this->hasFilledAttribute($link, 'aria-label')
                || $this->hasFilledAttribute($link, 'aria-labelledby')
                || $this->hasFilledAttribute($link, 'title')
                || $this->containsNamedDescendant($link);

            if (! $hasAccessibleName) {
                $findings[] = $this->makeFinding(AccessibilityRule::LinkText, $document->saveHtml($link));
            }
        }

        return ['checkedCount' => count($links), 'findings' => $findings];
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

    private function makeFinding(AccessibilityRule $rule, string $html): Finding
    {
        return new Finding(['accessibility_rule' => $rule, 'snippet' => Str::limit(Str::squish($html), self::MAX_SNIPPET_LENGTH)]);
    }
}
