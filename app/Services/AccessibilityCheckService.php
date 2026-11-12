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
     * The violations of all rules in the HTML, as unsaved findings.
     *
     * @return list<Finding>
     */
    public function findViolations(string $html): array
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        return [
            ...$this->findImagesWithoutAlt($document),
            ...$this->findMissingDocumentLanguage($document),
            ...$this->findMissingDocumentTitle($document),
            ...$this->findUnlabelledFormFields($document),
            ...$this->findLinksWithoutName($document),
        ];
    }

    /**
     * @return list<Finding>
     */
    private function findImagesWithoutAlt(HTMLDocument $document): array
    {
        $findings = [];

        foreach ($document->querySelectorAll('img:not([alt]):not([aria-hidden="true"]):not([role="presentation"]):not([role="none"])') as $image) {
            $findings[] = $this->makeFinding(AccessibilityRule::ImageAlt, $document->saveHtml($image));
        }

        return $findings;
    }

    /**
     * @return list<Finding>
     */
    private function findMissingDocumentLanguage(HTMLDocument $document): array
    {
        $root = $document->documentElement;

        if ($this->hasFilledAttribute($root, 'lang')) {
            return [];
        }

        return [$this->makeFinding(AccessibilityRule::DocumentLanguage, $this->openingTag($root))];
    }

    /**
     * @return list<Finding>
     */
    private function findMissingDocumentTitle(HTMLDocument $document): array
    {
        if ($document->title !== '') {
            return [];
        }

        return [$this->makeFinding(AccessibilityRule::DocumentTitle, $this->openingTag($document->head))];
    }

    /**
     * @return list<Finding>
     */
    private function findUnlabelledFormFields(HTMLDocument $document): array
    {
        $labelledIds = collect($document->querySelectorAll('label[for]'))->map(fn (Element $label) => $label->getAttribute('for'));

        $findings = [];

        foreach ($document->querySelectorAll('input, select, textarea') as $field) {
            $type = strtolower($field->getAttribute('type') ?? '');

            if ($field->tagName === 'INPUT' && in_array($type, self::INPUT_TYPES_WITHOUT_LABEL, true)) {
                continue;
            }

            $hasAccessibleName = $this->hasFilledAttribute($field, 'aria-label')
                || $this->hasFilledAttribute($field, 'aria-labelledby')
                || ($type === 'image' && $this->hasFilledAttribute($field, 'alt'))
                || $field->closest('label') !== null
                || $labelledIds->containsStrict($field->getAttribute('id'));

            if (! $hasAccessibleName) {
                $findings[] = $this->makeFinding(AccessibilityRule::FormLabel, $document->saveHtml($field));
            }
        }

        return $findings;
    }

    /**
     * @return list<Finding>
     */
    private function findLinksWithoutName(HTMLDocument $document): array
    {
        $findings = [];

        foreach ($document->querySelectorAll('a[href]') as $link) {
            $hasAccessibleName = filled($link->textContent)
                || $this->hasFilledAttribute($link, 'aria-label')
                || $this->hasFilledAttribute($link, 'aria-labelledby')
                || $this->hasFilledAttribute($link, 'title')
                || $this->containsNamedDescendant($link);

            if (! $hasAccessibleName) {
                $findings[] = $this->makeFinding(AccessibilityRule::LinkText, $document->saveHtml($link));
            }
        }

        return $findings;
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
        return new Finding(['accessibility_rule' => $rule, 'snippet' => Str::limit($html, self::MAX_SNIPPET_LENGTH)]);
    }
}
