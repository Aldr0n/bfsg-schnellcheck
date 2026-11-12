<?php

namespace App\Enums;

enum AccessibilityRule: string
{
    case ImageAlt = 'image_alt';
    case DocumentLanguage = 'document_language';
    case DocumentTitle = 'document_title';
    case FormLabel = 'form_label';
    case LinkText = 'link_text';

    public function label(): string
    {
        return match ($this) {
            self::ImageAlt => 'Alternativtext',
            self::DocumentLanguage => 'Sprache der Seite',
            self::DocumentTitle => 'Seitentitel',
            self::FormLabel => 'Feldbeschriftung',
            self::LinkText => 'Linktext',
        };
    }

    /**
     * The WCAG success criteria the rule belongs to.
     *
     * @return list<string>
     */
    public function wcagCriteria(): array
    {
        return match ($this) {
            self::ImageAlt => ['1.1.1'],
            self::DocumentLanguage => ['3.1.1'],
            self::DocumentTitle => ['2.4.2'],
            self::FormLabel => ['1.3.1', '4.1.2'],
            self::LinkText => ['2.4.4', '4.1.2'],
        };
    }

    public function explanation(): string
    {
        return match ($this) {
            self::ImageAlt => 'Dem Bild fehlt ein Alternativtext. Screenreader können es nicht beschreiben, Dekobilder brauchen ein leeres alt-Attribut.',
            self::DocumentLanguage => 'Der Seite fehlt die Sprachangabe. Screenreader wählen sonst die falsche Aussprache.',
            self::DocumentTitle => 'Die Seite hat keinen Titel. Er ist für viele Nutzer der erste Hinweis, wo sie sich befinden.',
            self::FormLabel => 'Dem Formularfeld fehlt eine Beschriftung. Ohne sie wissen Nutzer von Screenreadern nicht, was sie eintragen sollen.',
            self::LinkText => 'Der Link hat keinen erkennbaren Text. Nutzer von Screenreadern erfahren nicht, wohin er führt.',
        };
    }
}
