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
     * One sentence about how the rule went, from the number of checked elements and of findings.
     */
    public function summarySentence(int $checkedCount, int $findingCount): string
    {
        return match ($this) {
            self::ImageAlt => $this->sentenceByCount($checkedCount, $findingCount, 'Keine Bilder auf der Seite', 'Das Bild hat einen Alternativtext', 'Das Bild hat keinen Alternativtext', "Alle {$checkedCount} Bilder mit Alternativtext", "{$findingCount} von {$checkedCount} Bildern ohne Alternativtext"),
            self::FormLabel => $this->sentenceByCount($checkedCount, $findingCount, 'Keine Formularfelder auf der Seite', 'Das Feld ist beschriftet', 'Das Feld ist nicht beschriftet', "Alle {$checkedCount} Felder beschriftet", "{$findingCount} von {$checkedCount} Feldern ohne Beschriftung"),
            self::LinkText => $this->sentenceByCount($checkedCount, $findingCount, 'Keine Links auf der Seite', 'Der Link hat Text', 'Der Link hat keinen Text', "Alle {$checkedCount} Links mit Text", "{$findingCount} von {$checkedCount} Links ohne Text"),
            self::DocumentLanguage => $findingCount === 0 ? 'Sprache ist angegeben' : 'Sprachangabe fehlt',
            self::DocumentTitle => $findingCount === 0 ? 'Seite hat einen Titel' : 'Titel fehlt',
        };
    }

    private function sentenceByCount(int $checkedCount, int $findingCount, string $noElementsSentence, string $singlePassedSentence, string $singleFailedSentence, string $allPassedSentence, string $someFailedSentence): string
    {
        if ($checkedCount === 0) {
            return $noElementsSentence;
        }

        if ($checkedCount === 1) {
            return $findingCount === 0 ? $singlePassedSentence : $singleFailedSentence;
        }

        return $findingCount === 0 ? $allPassedSentence : $someFailedSentence;
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
