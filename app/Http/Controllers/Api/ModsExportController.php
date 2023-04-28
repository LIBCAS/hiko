<?php

/* TODO:
 * - Refactor!
 */

namespace App\Http\Controllers\Api;

use App\Models\Letter;
use App\Http\Controllers\Controller;

class ModsExportController extends Controller
{
    protected $langCodes = [
        'en' => 'eng',
        'cs' => 'cze',
    ];

    public function __invoke()
    {
        $result = '<?xml version="1.0" encoding="UTF-8"?><mods_records>';

        Letter::where('status', '=', 'publish')
            ->with('identities', 'places', 'keywords')
            ->get()
            ->map(function ($letter) {
                $letter['identities_grouped'] = $letter->identities->groupBy('pivot.role')->toArray();
                $letter['places_grouped'] = $letter->places->groupBy('pivot.role')->toArray();
                return $letter;
            })
            ->each(function ($letter) use (&$result) {
                $result .= $this->createRecord($letter);
            });

        $result .= '</mods_records>';

        return response($result, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    protected function createRecord(Letter $letter)
    {
        //$record = '<mods version="3.7" xsi:schemaLocation="http://www.loc.gov/mods/v3 https://www.loc.gov/standards/mods/v3/mods-3-7.xsd">';
        $record = '<mods version="3.7">';
        $record .= '<recordInfo>';
        $record .= "<recordIdentifier>{$letter->uuid}</recordIdentifier>";
        $record .= '<recordOrigin>' .  config('app.name') . '</recordOrigin>';
        $record .= '</recordInfo>';
        $record .= "<titleInfo><title>{$letter->name}</title></titleInfo>";
        $record .= $this->dateCreated($letter);
        $record .= $this->notes($letter);
        $record .= $this->identity($letter, 'author');
        $record .= $this->identity($letter, 'recipient');
        $record .= $this->place($letter, 'origin');
        $record .= $this->place($letter, 'destination');
        $record .= $this->languages($letter->languages);
        $record .= $this->keywords($letter->keywords);
        $record .= $this->mentioned($letter, 'mentioned');
        $record .= $this->abstract($letter->getTranslations('abstract'));
        $record .= $this->incipit($letter->incipit);
        $record .= $this->explicit($letter->explicit);
        $record .= $this->related($letter->related_resources);
        $record .= $this->copies($letter->copies);
        $record .= $this->copyright($letter->copyright);

        return "{$record}</mods>";
    }

    protected function dateCreated($letter)
    {
        $dateCreatedStart = $this->formatDate($letter->date_day, $letter->date_month, $letter->date_year);
        $dateCreatedEnd = $letter->date_is_range ? $this->formatDate($letter->date_day, $letter->date_month, $letter->date_year) : null;

        $qualifiers = array_filter([
            $letter->date_uncertain ? 'questionable' : null,
            $letter->date_inferred ? 'inferred' : null,
            $letter->date_approximate ? 'approximate' : null,
        ]);

        $qualifiers = 'qualifier="' . implode(' ', $qualifiers) . '"';


        if ($dateCreatedStart && $dateCreatedEnd) {
            $result = "<dateCreated {$qualifiers}>{$dateCreatedStart}</dateCreated>";
            $result .= "<dateCreated _attributes=\"start\">{$dateCreatedStart}</dateCreated>";
            $result .= "<dateCreated _attributes=\"end\">{$dateCreatedEnd}</dateCreated>";
            return $result;
        }

        return "<dateCreated {$qualifiers}>{$dateCreatedStart}</dateCreated>";
    }

    protected function formatDate($day, $month, $year)
    {
        if (!$day && !$month && !$year) {
            return null;
        }

        $date = $day ? "{$day}." : '';
        $date .= $month ? "{$month}." : '';
        $date .= $year ? "{$year}" : '';

        return $date;
    }

    protected function notes($letter)
    {
        $notes = '';

        $types = [
            'date_note' => 'date',
            'author_note' => 'statement of responsibility',
            'recipient_note' => 'recipient',
            'destination_note' => 'destination',
            'origin_note' => 'origin',
            'people_mentioned_note' => 'people mentioned',
            'notes_public' => '',
        ];

        foreach ($types as $key => $type) {
            if ($letter->{$key}) {
                $note = str_replace('"', "'", $letter->{$key});
                $notes .= "<note type=\"{$type}\">{$note}</note>";
            }
        }

        return $notes;
    }

    protected function identity($letter, $type)
    {
        if (!isset($letter->identities_grouped[$type])) {
            return '';
        }

        $qualifiers = array_filter([
            $letter->{$type . '_inferred'} ? 'inferred' : null,
            $letter->{$type . '_uncertain'} ? 'questionable' : null,
        ]);

        $qualifiers = 'qualifier="' . implode(' ', $qualifiers) . '"';

        $identities = '';

        foreach ($letter->identities_grouped[$type] as $identity) {
            $identities .= "<name {$qualifiers} type=\"";
            $identities .= $identity['type'] === 'institution' ? 'corporate' : 'personal';
            $identities .= '">';
            $identities .= '<namePart>' . str_replace('"', "'", $identity['name']) . '</namePart>';
            $identities .= $identity['pivot']['marked']
                ? '<displayForm>' . str_replace('"', "'", $identity['pivot']['marked']) . '</displayForm>'
                : '';
            $identities .= $identity['birth_year'] || $identity['death_year']
                ? "<namePart type=\"date\">{$identity['birth_year']}-{$identity['death_year']}</namePart>"
                : '';
            $identities .= '<role><roleTerm type="text">' . $type . '</roleTerm></role>';
            $identities .= $identity['pivot']['salutation']
                ? '<salut>' . str_replace('"', "'", $identity['pivot']['salutation']) . '</salut>'
                : '';
            $identities .= $identity['viaf_id']
                ? '<nameIdentifier>' . str_replace('"', "'", $identity['viaf_id']) . '</nameIdentifier>'
                : '';

            $identities .= '</name>';
        }

        return $identities;
    }

    protected function place($letter, $type)
    {
        if (!isset($letter->places_grouped[$type])) {
            return '';
        }

        $qualifiers = array_filter([
            $letter->{$type . '_inferred'} ? 'inferred' : null,
            $letter->{$type . '_uncertain'} ? 'questionable' : null,
        ]);

        $qualifiers = 'qualifier="' . implode(' ', $qualifiers) . '"';

        $places = '';

        foreach ($letter->places_grouped[$type] as $place) {
            $places .= "<originInfo {$qualifiers} type=\"{$type}\">";
            $places .= '<place><placeTerm>' . str_replace('"', "'", $place['name']) . '</placeTerm>';
            $places .= $place['pivot']['marked']
                ? '<placeTerm type="display">' . str_replace('"', "'", $place['pivot']['marked']) . '</placeTerm>'
                : '';
            $places .= $place['geoname_id']
                ? '<placeTerm type="authority">' . str_replace('"', "'", $place['geoname_id']) . '</placeTerm>'
                : '';
            $places .= '</place>';
            $places .= '</originInfo>';
        }

        return $places;
    }

    protected function languages($languages)
    {
        $result = '';
        foreach (explode(';', $languages) as $lang) {
            $result .= '<language><languageTerm type="text">';
            $result .= $lang;
            $result .= '</languageTerm></language>';
        }

        return $result;
    }

    protected function keywords($keywords)
    {
        if (!$keywords) {
            return '';
        }

        $result = '';

        foreach ($keywords as $kw) {
            $translations = $kw->getTranslations('name');

            foreach ($translations as $lang => $translation) {
                $result .= '<subject><topic lang="' . $this->langCodes[$lang] . '">';
                $result .= $translation;
                $result .= '</topic></subject>';
            }
        }

        return $result;
    }

    protected function mentioned($letter)
    {
        if (!isset($letter->identities_grouped['mentioned'])) {
            return '';
        }

        $result = '';

        foreach ($letter->identities_grouped['mentioned'] as $identity) {
            $result .= "<subject><topic><name type=\">";
            $result .= $identity['type'] === 'institution' ? 'corporate' : 'personal';
            $result .= '">';
            $result .= '<namePart>' . str_replace('"', "'", $identity['name']) . '</namePart>';
            $result .= $identity['birth_year'] || $identity['death_year']
                ? "<namePart type=\"date\">{$identity['birth_year']}-{$identity['death_year']}</namePart>"
                : '';
            $result .= $identity['viaf_id']
                ? '<nameIdentifier>' . str_replace('"', "'", $identity['viaf_id']) . '</nameIdentifier>'
                : '';
            $result .= '</name></topic></subject>';
        }

        return $result;
    }

    protected function abstract($abstract)
    {
        $result = '';

        foreach ($abstract as $lang => $translation) {
            $result .= '<abstract lang="' . $this->langCodes[$lang] . '">';
            $result .= $translation;
            $result .= '</abstract>';
        }

        return $result;
    }

    protected function incipit($incipit)
    {
        return $incipit
            ? '<incipit>' . $incipit . '</incipit>'
            : '';
    }

    protected function explicit($explicit)
    {
        return $explicit
            ? '<explicit>' . $explicit . '</explicit>'
            : '';
    }

    protected function related($resources)
    {
        if (!$resources) {
            return '';
        }

        $result = '';


        foreach ($resources as $resource) {
            $title = str_replace('"', "'", $resource['title']);

            $result .= !empty($resource['link']) ?
                '<relatedItem displayLabel="' . $title . '" href="' . $resource['link'] . '" />'
                : '<relatedItem displayLabel="' . $title . '"/>';
        }

        return $result;
    }

    protected function copies($copies)
    {
        if (!$copies) {
            return '';
        }

        $result = '<location>';

        foreach ((array) $copies as $c) {
            if ($c['l_number']) {
                $result .= '<shelfLocator>' . str_replace('"', "'", $c['l_number']) . '</shelfLocator>';
            }

            if ($c['repository']) {
                $result .= '<repository>' . str_replace('"', "'", $c['repository']) . '</repository>';
            }

            if ($c['archive']) {
                $result .= '<archive>' . str_replace('"', "'", $c['archive']) . '</archive>';
            }

            if ($c['collection']) {
                $result .= '<collection>' . str_replace('"', "'", $c['collection'])  . '</collection>';
            }

            if ($c['signature']) {
                $result .= '<shelfLocator>' . str_replace('"', "'", $c['signature'])  . '</shelfLocator>';
            }

            if ($c['type']) {
                $result .= '<genre type="documentType">' . str_replace('"', "'", $c['type']) . '</genre>';
            }

            if ($c['preservation']) {
                $result .= '<shelfLocator type="preservation">' . str_replace('"', "'", $c['preservation']) . '</shelfLocator>';
            }

            if ($c['copy']) {
                $result .= '<form>' . str_replace('"', "'", $c['copy']) . '</form>';
            }

            if ($c['location_note']) {
                $result .= '<note type="location">' . str_replace('"', "'", $c['location_note']) . '</note>';
            }

            if ($c['manifestation_notes']) {
                $result .= '<note type="manifestation">' . str_replace('"', "'", $c['manifestation_notes']) . '</note>';
            }
        }

        $result .= '</location>';
        return $result;
    }

    protected function copyright($copyright)
    {
        return $copyright
            ? '<rights>' . $copyright . '</rights>'
            : '';
    }
}
