<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class OAIPMHController extends Controller
{
    public function handle(Request $request)
    {
        $verb = $request->query('verb');

        switch ($verb) {
            case 'Identify':
                return $this->identify();
            case 'ListMetadataFormats':
                return $this->listMetadataFormats();
            case 'ListRecords':
                return $this->listRecords($request);
            case 'GetRecord':
                return $this->getRecord($request);
            default:
                return $this->errorResponse('badVerb', 'Unsupported OAI-PMH verb.');
        }
    }

    private function identify()
    {
        $response = [
            'repositoryName' => 'HIKO Repository',
            'baseURL' => route('oai-pmh'),
            'protocolVersion' => '2.0',
            'adminEmail' => 'admin@example.com',
            'earliestDatestamp' => '2000-01-01T00:00:00Z',
            'deletedRecord' => 'no',
            'granularity' => 'YYYY-MM-DDThh:mm:ssZ',
        ];
        return $this->xmlResponse('Identify', $response);
    }

    private function listMetadataFormats()
    {
        $formats = [
            [
                'prefix' => 'oai_dc',
                'schema' => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
                'namespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/'
            ]
        ];
        return $this->xmlResponse('ListMetadataFormats', ['metadataFormats' => $formats]);
    }

    private function listRecords(Request $request)
    {
        $from = $request->query('from', '2000-01-01T00:00:00Z');
        $until = $request->query('until', now()->toIso8601String());
        $metadataPrefix = $request->query('metadataPrefix', 'oai_dc');

        if ($metadataPrefix !== 'oai_dc') {
            return $this->errorResponse('cannotDisseminateFormat', 'The metadata format is not supported.');
        }

        $records = $this->fetchRecords($from, $until);
        return $this->xmlResponse('ListRecords', ['records' => $records]);
    }

    private function getRecord(Request $request)
    {
        $identifier = $request->query('identifier');
        $metadataPrefix = $request->query('metadataPrefix', 'oai_dc');

        if ($metadataPrefix !== 'oai_dc') {
            return $this->errorResponse('cannotDisseminateFormat', 'The metadata format is not supported.');
        }

        $record = $this->fetchRecordByIdentifier($identifier);

        if (!$record) {
            return $this->errorResponse('idDoesNotExist', 'The requested identifier does not exist.');
        }

        return $this->xmlResponse('GetRecord', ['record' => $record]);
    }

    private function fetchRecords($from, $until)
    {
        $letters = \App\Models\Letter::with(['authors', 'recipients', 'keywords', 'origins', 'destinations'])
            ->whereBetween('updated_at', [$from, $until])
            ->get();

        return $letters->map(function ($letter) {
            return [
                'identifier' => $letter->uuid,
                'datestamp' => $letter->updated_at->toIso8601String(),
                'metadata' => $this->formatMetadata($letter),
            ];
        })->toArray();
    }

    private function fetchRecordByIdentifier($identifier)
    {
        $letter = \App\Models\Letter::with(['authors', 'recipients', 'keywords', 'origins', 'destinations'])
            ->where('uuid', $identifier)
            ->first();

        if (!$letter) {
            return null;
        }

        return [
            'identifier' => $letter->uuid,
            'datestamp' => $letter->updated_at->toIso8601String(),
            'metadata' => $this->formatMetadata($letter),
        ];
    }

    private function formatMetadata($letter)
    {
        return [
            'title' => $letter->abstract,
            'creator' => $letter->authors->pluck('name')->implode(', '),
            'subject' => $letter->keywords->pluck('name')->implode(', '),
            'description' => $letter->content,
            'date' => $letter->date_computed,
            'type' => 'Correspondence',
            'format' => 'Text',
            'identifier' => $letter->uuid,
            'language' => $letter->languages,
            'rights' => $letter->copyright,
            'relation' => [
                'origin' => $letter->origins->pluck('name')->implode(', '),
                'destination' => $letter->destinations->pluck('name')->implode(', '),
            ],
        ];
    }

    private function xmlResponse($verb, $data)
    {
        $xml = view("oai-pmh.$verb", $data)->render();
        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function errorResponse($code, $message)
    {
        return Response::make("
            <OAI-PMH xmlns='http://www.openarchives.org/OAI/2.0/'>
                <responseDate>" . now()->toIso8601String() . "</responseDate>
                <error code='$code'>$message</error>
            </OAI-PMH>
        ", 200, ['Content-Type' => 'application/xml']);
    }
}
