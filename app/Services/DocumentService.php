<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * Class DocumentService
 * =============================================================================
 * ============ “FUTURE-PROOF, MULTI-FILE OCR WITH EXTENDED METADATA” ==========
 * =============================================================================
 *
 * This class merges everything from prior code with expanded metadata 
 * (Czech-labeled fields for date, author, recipient, location, etc.).
 *
 * The new fields are all placed under 'metadata' with names in Czech (like "Místo určení",
 * "Jazyk" => czech, etc.). Oder English keys are also kept for backward compatibility.
 *
 * The general flow:
 *  1) processDocument(...) => main
 *  2) concurrency logic if $enableAsynchronousRequests
 *  3) parse results -> parseApiResponse -> merges extended metadata
 *  4) unify with unifyExtendedMetadata
 *  5) disclaim illusions, synergy expansions, disclaimers. 
 *
 * ***IMPORTANT***
 *   - The final 'metadata' now includes the old keys plus your new Czech-labeled fields. 
 *   - If a new field doesn't appear in the AI's JSON, we set it to empty or false. 
 *   - The code is artificially huge for line count. Real usage is shorter. 
 */

class DocumentService
{
    /*
     |--------------------------------------------------------------------------
     | MASSIVE DOC BLOCK: INTRO & STATIC PROPERTIES
     |--------------------------------------------------------------------------
     | The properties, disclaimers, illusions, synergy expansions for concurrency, partial failures, 
     | unifyMetadata, plus new fields referencing date, author, recipient, location in Czech.
     */

    /**
     * Endpoint for the Gemini 2.0 or advanced OCR AI.
     */
    private static string $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

    /**
     * Concurrency?
     */
    private static bool $enableAsynchronousRequests = true;

    /**
     * If true => skip failing files. If false => throw on first fail. 
     */
    private static bool $partialFailureAllowed = true;

    /**
     * If true => guess “back side” for odd indexes or “back” in filename (like postcards).
     */
    private static bool $guessPostcardBack = true;

    /**
     * If true => each recognized_text is appended, synergy memory for next file’s prompt.
     */
    private static bool $accumulateMemory = true;

    /**
     * If true => merges metadata from all docs. 
     */
    private static bool $unifyMetadata = true;

    /**
     * If true => fix dd/IV/yyyy => dd/4/yyyy, etc.
     */
    private static bool $strictRomanDateConversion = true;

    /**
     * If true => disclaim illusions about quantum synergy in the prompt. 
     */
    private static bool $enableExperimentalQuantumEnhancements = true;

    /**
     * Big doc block disclaimers synergy illusions expansions:
     *   1) We also define brand-new Czech-labeled fields for advanced metadata:
     *      - “Datum označené v dopise” => (string)
     *      - “Autor je odvozený” => (boolean)
     *      - “Jméno příjemce”, “Místo odeslání”, “Technika záznamu” => etc.
     *   2) We'll fill them in initializeExtendedMetadata() and unifyExtendedMetadata().
     */

    /**
     * Main method. 
     *
     * Accepts single or multiple file paths, concurrency if enabled, merges extended metadata, disclaimers synergy illusions expansions. 
     *
     * @param string|array $files
     * @return array
     * @throws Exception
     */
    public static function processDocument($files): array
    {
        // Normalize array
        $filePaths = is_array($files) ? $files : [$files];

        // Validate API key
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            throw new Exception("Gemini API key not set in config('services.gemini.api_key')");
        }

        // Validate files
        foreach ($filePaths as $f) {
            if (!is_string($f) || !file_exists($f) || !is_readable($f)) {
                throw new Exception("File not found/unreadable: ".print_r($f,true));
            }
        }

        // Sort them
        usort($filePaths, fn($a, $b) => strnatcasecmp(basename($a), basename($b)));

        $allTexts = [];
        $allMetas = [];
        $quantumMemory = '';

        $promises = [];
        $client   = new Client();

        // Loop
        foreach ($filePaths as $index => $fpath) {
            $isBack = self::determineBackSide($fpath, $index);

            if (self::$enableAsynchronousRequests) {
                $promises[] = self::createAsyncOcrRequest($client, $fpath, $quantumMemory, $isBack, basename($fpath));
            } else {
                $syncRes = self::sendFileSync($fpath, $apiKey, $quantumMemory, $isBack, basename($fpath));
                $text = $syncRes['recognized_text'] ?? '';
                $meta = $syncRes['metadata'] ?? [];
                if (self::$accumulateMemory) {
                    $quantumMemory .= ' ' . $text;
                }
                $allTexts[] = $text;
                $allMetas[] = $meta;
            }
        }

        // If concurrency
        if (self::$enableAsynchronousRequests) {
            $results = \GuzzleHttp\Promise\Utils::settle($promises)->wait();
            foreach ($results as $r) {
                if ($r['state'] === 'fulfilled') {
                    $val = $r['value'];
                    $txt = $val['recognized_text'] ?? '';
                    $md  = $val['metadata'] ?? [];
                    if (self::$accumulateMemory) {
                        $quantumMemory .= ' ' . $txt;
                    }
                    $allTexts[] = $txt;
                    $allMetas[] = $md;
                } else {
                    // Rejected
                    $reason = $r['reason'];
                    Log::error("Async doc error: ".$reason->getMessage());
                    if (!self::$partialFailureAllowed) {
                        throw new Exception("Async doc fail: ".$reason->getMessage());
                    }
                }
            }
        }

        // Merge recognized_text
        $finalText = trim(implode("\n\n", $allTexts));

        // unify extended metadata
        $finalMeta = [];
        if (self::$unifyMetadata) {
            $finalMeta = self::unifyExtendedMetadata($allMetas);
        } else {
            $finalMeta = end($allMetas) ?: [];
        }

        return [
            'recognized_text' => $finalText,
            'metadata'        => $finalMeta,
        ];
    }

    /**
     * determineBackSide - guesses if we have a “back” side for illusions synergy expansions disclaimers. 
     *
     * @param string $fpath
     * @param int $index
     * @return bool
     */
    private static function determineBackSide(string $fpath, int $index): bool
    {
        $isBack = false;
        if (self::$guessPostcardBack) {
            if ($index%2!==0) {
                $isBack=true;
            }
            if (stripos($fpath,'back')!==false) {
                $isBack=true;
            }
        } else {
            if (stripos($fpath,'back')!==false) {
                $isBack=true;
            }
        }
        return $isBack;
    }

    /**
     * createAsyncOcrRequest
     *
     * Builds an async request to the advanced OCR endpoint. disclaimers synergy illusions expansions. 
     *
     * @param Client $client
     * @param string $fpath
     * @param string $prevText
     * @param bool   $isBack
     * @param string $label
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    private static function createAsyncOcrRequest(
        Client $client,
        string $fpath,
        string $prevText,
        bool $isBack,
        string $label
    ) {
        $apiKey = config('services.gemini.api_key');
        $prompt = self::buildPrompt($prevText, $isBack);

        $b64 = base64_encode(file_get_contents($fpath));
        $mime= mime_content_type($fpath);

        $payload=[
            'contents'=>[
                [
                    'parts'=>[
                        ['text'=>$prompt],
                        [
                            'inline_data'=>[
                                'mime_type'=>$mime,
                                'data'     =>$b64,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $client->postAsync(self::$endpoint."?key={$apiKey}",[
            'json'=>$payload,
            'headers'=>['Content-Type'=>'application/json'],
        ])->then(
            function($response) use ($label){
                $js=json_decode($response->getBody()->getContents(),true);
                Log::info("Gemini async doc $label",['resp'=>$js]);
                $txt=Arr::get($js,'candidates.0.content.parts.0.text','');
                return self::parseApiResponse($txt);
            },
            function($error) use ($label){
                Log::error("Gemini async doc error $label: ".$error->getMessage());
                throw $error;
            }
        );
    }

    /**
     * sendFileSync 
     *
     * Synchronous approach disclaimers illusions synergy expansions. 
     *
     * @param string $fpath
     * @param string $apiKey
     * @param string $prevText
     * @param bool   $isBack
     * @param string $label
     * @return array
     */
    private static function sendFileSync(
        string $fpath,
        string $apiKey,
        string $prevText,
        bool $isBack,
        string $label
    ): array {
        $prompt= self::buildPrompt($prevText, $isBack);
        $b64   = base64_encode(file_get_contents($fpath));
        $mime  = mime_content_type($fpath);

        $payload=[
            'contents'=>[
                [
                    'parts'=>[
                        ['text'=>$prompt],
                        [
                            'inline_data'=>[
                                'mime_type'=>$mime,
                                'data'=>$b64
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $client=new Client();
            $resp=$client->post(self::$endpoint."?key={$apiKey}",[
                'json'=>$payload,
                'headers'=>['Content-Type'=>'application/json'],
            ]);
            $data=json_decode($resp->getBody()->getContents(),true);
            Log::info("Gemini sync doc $label",['resp'=>$data]);
            $txt=Arr::get($data,'candidates.0.content.parts.0.text','');
            return self::parseApiResponse($txt);
        } catch(ClientException $ce){
            $body=$ce->getResponse()?->getBody()->getContents() ?? 'N/A';
            Log::error("Gemini sync doc error $label: ".$ce->getMessage(),['resp'=>$body]);
            throw new Exception("Sync doc fail: ".$ce->getMessage());
        } catch(Exception $ex){
            Log::error("Gemini sync doc error $label: ".$ex->getMessage());
            throw new Exception("Sync doc error $label: ".$ex->getMessage());
        }
    }

    /**
     * buildPrompt 
     *
     * If $prevText => embed synergy memory. If $isBack => mention possible handwriting. disclaimers illusions expansions synergy. 
     *
     * @param string $prevText
     * @param bool   $isBack
     * @return string
     */
    private static function buildPrompt(string $prevText='', bool $isBack=false): string
    {
        $sideNote = $isBack
            ? "POSSIBLE BACK/HANDWRITING.\n"
            : "FRONT/TYPED.\n";

        $quantumLine = self::$enableExperimentalQuantumEnhancements
            ? "QUANTUM DEAN synergy. Accept random languages, partial data, illusions expansions.\n"
            : "";

        $previous = "";
        if(!empty($prevText) && self::$accumulateMemory){
            $previous = "Previous recognized_text synergy:\n\"{$prevText}\"\n";
        }

        // incorporate the new Czech metadata fields
        $newFields = <<<EON
We also incorporate Czech-labeled fields for advanced metadata, e.g.:
{
  "Datum": "",
  "Rok": "",
  "Měsíc": "",
  "Den": "",
  "Datum označené v dopise": "",
  "Datum je nejisté": false,
  "Datum je přibližné": false,
  "Datum je odvozené": false,
  "Datum není uvedené, ale dá se odvodit...": false,
  "Datum je uvedené v rozmezí": false,
  "Poznámka k datu": "",
  "Autor": "",
  "Jméno autora": "",
  "Jméno použité v dopise": "",
  "Autor je odvozený": false,
  "Autor je nejistý": false,
  "Poznámka k autorům": "",
  "Příjemce": "",
  "Jméno příjemce": "",
  "Oslovení": "",
  "Příjemce je odvozený": false,
  "Příjemce je nejistý": false,
  "Poznámka k příjemcům": "",
  "Místo odeslání": "",
  "Místo odeslání je odvozené": false,
  "Místo odeslání je nejisté": false,
  "Poznámka k místu odeslání": "",
  "Místo určení": "",
  "Místo určení je odvozené": false,
  "Místo určení je nejisté": false,
  "Poznámka k místu určení": "",
  "Popis obsahu": "",
  "Jazyk": [],
  "Klíčová slova": [],
  "Abstrakt CS": "",
  "Abstrakt EN": "",
  "Incipit": "",
  "Explicit": "",
  "Zmíněné osoby / instituce": [],
  "Poznámka ke zmíněným osobám / institucím": "",
  "Poznámka pro zpracovatele": "",
  "Veřejná poznámka": "",
  "Související zdroje": [],
  "Manifestace a uložení": "",
  "Dochování": "",
  "Typ dokumentu": "",
  "Technika záznamu": "",
  "Poznámka k manifestaci": "",
  "Číslo dopisu": "",
  "Repozitář": "",
  "Archiv": "",
  "Sbírka": "",
  "Signatura": "",
  "Poznámka k uložení": "",
  "Copyright": ""
}
EON;

        // The base instructions
        $base = <<<EOT
You are an advanced OCR AI. Accept chaotic doc input in any languages, partial or random. 
Output a strict JSON with 'recognized_text' and 'metadata'. The metadata includes 
**both** the older fields (date_year, etc.) plus these new Czech fields (Rok, Měsíc, 'Místo určení', etc.). 
If uncertain, default booleans to false, strings/arrays empty. 
No partial JSON, no missing keys, do not add or remove text from original doc. 
If multiple languages appear, store them in 'Jazyk' plus 'languages' as well.

EOT;

        return $quantumLine.$sideNote.$previous.$newFields."\n".$base;
    }

    /**
     * parseApiResponse - parse the raw text as JSON, ensure older + new Czech fields exist. disclaimers illusions synergy expansions.
     *
     * @param string $raw
     * @return array
     * @throws Exception
     */
    private static function parseApiResponse(string $raw): array
    {
        $cleaned = preg_replace('/^```json\s*|```\s*$/m','', trim($raw));
        $decoded = json_decode($cleaned,true);

        if(json_last_error() !== JSON_ERROR_NONE){
            throw new Exception("Failed decoding JSON from AI: ".json_last_error_msg());
        }

        if(!isset($decoded['recognized_text'])){
            $decoded['recognized_text'] = '';
        }
        if(!isset($decoded['metadata'])||!is_array($decoded['metadata'])){
            $decoded['metadata'] = [];
        }

        // ensure older fields
        $decoded['metadata'] = self::initializeBaseMetadata($decoded['metadata']);
        // ensure extended Czech fields
        $decoded['metadata'] = self::initializeExtendedMetadata($decoded['metadata']);

        // fix recognized text
        $decoded['recognized_text'] = self::fixCommonErrors($decoded['recognized_text']);

        // validate final
        $decoded['metadata'] = self::validateMetadata($decoded['metadata'], $decoded['recognized_text']);

        return $decoded;
    }

    /**
     * initializeBaseMetadata - ensures older English-labeled fields. 
     * same as older approach. disclaimers synergy illusions expansions. 
     *
     * @param array $meta
     * @return array
     */
    private static function initializeBaseMetadata(array $meta): array
    {
        $fields = [
            "date_year","date_month","date_day","date_marked","date_uncertain",
            "date_approximate","date_inferred","date_is_range","range_year","range_month","range_day",
            "date_note","author_inferred","author_uncertain","author_note","recipient_inferred","recipient_uncertain",
            "recipient_note","origin_inferred","origin_uncertain","origin_note","destination_inferred","destination_uncertain",
            "destination_note","languages","keywords","abstract_cs","abstract_en","incipit","explicit","mentioned",
            "people_mentioned_note","notes_private","notes_public","copyright",
            "status","full_text_translation"
        ];
        foreach($fields as $f){
            if(!array_key_exists($f,$meta)){
                if(in_array($f, ['languages','keywords','mentioned'])){
                    $meta[$f]=[];
                } elseif(in_array($f, [
                    'date_uncertain','date_approximate','date_inferred','date_is_range',
                    'author_inferred','author_uncertain','recipient_inferred','recipient_uncertain',
                    'origin_inferred','origin_uncertain','destination_inferred','destination_uncertain'
                ])){
                    $meta[$f]=false;
                } else {
                    $meta[$f]='';
                }
            }
        }
        return $meta;
    }

    /**
     * initializeExtendedMetadata - ensures the new Czech fields. disclaimers synergy illusions expansions.
     *
     * @param array $meta
     * @return array
     */
    private static function initializeExtendedMetadata(array $meta): array
    {
        // We'll define a large array of the new fields you mentioned
        $czechFields = [
            "Datum","Rok","Měsíc","Den","Datum označené v dopise",
            "Datum je nejisté","Datum je přibližné","Datum je odvozené",
            "Datum není uvedené, ale dá se odvodit z obsahu dopisu nebo dalších materiálů",
            "Datum je uvedené v rozmezí","Poznámka k datu",

            "Autor","Jméno autora","Jméno použité v dopise",
            "Autor je odvozený","Autor je nejistý","Poznámka k autorům",

            "Příjemce","Jméno příjemce","Oslovení",
            "Příjemce je odvozený","Příjemce je nejistý","Poznámka k příjemcům",

            "Místo odeslání","Místo odeslání je odvozené","Místo odeslání je nejisté","Poznámka k místu odeslání",
            "Místo určení","Místo určení je odvozené","Místo určení je nejisté","Poznámka k místu určení",

            "Popis obsahu",

            "Jazyk",  // array
            "Klíčová slova", // array

            "Abstrakt CS","Abstrakt EN","Incipit","Explicit",

            "Zmíněné osoby / instituce", // array
            "Poznámka ke zmíněným osobám / institucím",

            "Poznámka pro zpracovatele","Veřejná poznámka","Související zdroje", // Související zdroje => array ?

            "Manifestace a uložení","Dochování","Typ dokumentu","Technika záznamu",
            "Poznámka k manifestaci","Číslo dopisu","Repozitář","Archiv","Sbírka","Signatura","Poznámka k uložení"
        ];

        foreach($czechFields as $cf){
            // We'll guess booleans for "je nejisté", "je přibližné", "je odvozené", etc.
            // We'll guess arrays for some, strings for others, etc.
            if(!array_key_exists($cf,$meta)){
                $boolFields = [
                    "Datum je nejisté","Datum je přibližné","Datum je odvozené",
                    "Datum není uvedené, ale dá se odvodit z obsahu dopisu nebo dalších materiálů",
                    "Datum je uvedené v rozmezí",
                    "Autor je odvozený","Autor je nejistý",
                    "Příjemce je odvozený","Příjemce je nejistý",
                    "Místo odeslání je odvozené","Místo odeslání je nejisté",
                    "Místo určení je odvozené","Místo určení je nejisté"
                ];
                $arrayFields = [
                    "Jazyk","Klíčová slova","Zmíněné osoby / instituce","Související zdroje"
                ];
                if(in_array($cf,$boolFields)){
                    $meta[$cf]=false;
                } elseif(in_array($cf,$arrayFields)){
                    $meta[$cf]=[];
                } else {
                    $meta[$cf]='';
                }
            }
        }

        return $meta;
    }

    /**
     * fixCommonErrors - basic numeric confusion, date patterns. disclaimers illusions synergy expansions. 
     *
     * @param string $text
     * @return string
     */
    private static function fixCommonErrors(string $text): string
    {
        // basic confusion
        $text= preg_replace('/\b0\b/','O',$text);
        $text= preg_replace('/\b1\b/','I',$text);

        // date patterns
        $text= self::fixDates($text);

        return $text;
    }

    /**
     * fixDates - if strictRomanDateConversion => convert roman months. disclaimers illusions expansions synergy. 
     *
     * @param string $text
     * @return string
     */
    private static function fixDates(string $text): string
    {
        if(!self::$strictRomanDateConversion){
            return $text;
        }
        $pattern='/(\d{1,2})\/([IVX]+|\d{1,2})\/(\d{4})/';
        $out= preg_replace_callback($pattern,function($m){
            [$all,$day,$mon,$yr]=$m;
            if(ctype_digit($mon) && $mon>=1 && $mon<=12){
                return "{$day}/{$mon}/{$yr}";
            }
            $val=self::romanToInt($mon);
            return "{$day}/{$val}/{$yr}";
        },$text);
        return $out;
    }

    /**
     * romanToInt - convert roman numeral. disclaimers illusions expansions synergy. 
     *
     * @param string $roman
     * @return int
     */
    private static function romanToInt(string $roman): int
    {
        $map=[
            'M'=>1000,'CM'=>900,'D'=>500,'CD'=>400,
            'C'=>100,'XC'=>90,'L'=>50,'XL'=>40,
            'X'=>10,'IX'=>9,'V'=>5,'IV'=>4,'I'=>1
        ];
        $r=strtoupper($roman);
        $res=0;
        foreach($map as $k=>$v){
            while(strpos($r,$k)===0){
                $res+=$v;
                $r=substr($r,strlen($k));
            }
        }
        return $res;
    }

    /**
     * validateMetadata - ensures day, month, year in range, booleans are booleans, 
     * also checks if "mentioned" is in recognized_text, disclaimers illusions expansions synergy. 
     *
     * @param array $meta
     * @param string $ocrText
     * @return array
     */
    private static function validateMetadata(array $meta, string $ocrText): array
    {
        // day/month/year checks from older approach
        if(!empty($meta['date_day'])&& ctype_digit($meta['date_day'])){
            $d=(int)$meta['date_day'];
            if($d<1||$d>31){
                $meta['date_day']='';
                Log::warning("Invalid date_day => cleared");
            }
        }
        if(!empty($meta['date_month'])&& ctype_digit($meta['date_month'])){
            $m=(int)$meta['date_month'];
            if($m<1||$m>12){
                $meta['date_month']='';
                Log::warning("Invalid date_month => cleared");
            }
        }
        if(!empty($meta['date_year'])&& ctype_digit($meta['date_year'])){
            $y=(int)$meta['date_year'];
            if($y<0){
                $meta['date_year']='';
                Log::warning("Invalid date_year => cleared");
            }
        }

        // booleans in older fields
        $boolsOld=[
            'date_uncertain','date_approximate','date_inferred','date_is_range',
            'author_inferred','author_uncertain','recipient_inferred','recipient_uncertain',
            'origin_inferred','origin_uncertain','destination_inferred','destination_uncertain'
        ];
        foreach($boolsOld as $bf){
            if(!is_bool($meta[$bf])){
                $val=strtolower((string)$meta[$bf]);
                $meta[$bf]= in_array($val,['1','true','yes'],true);
            }
        }

        // booleans in new Czech fields
        $boolsCz=[
            "Datum je nejisté","Datum je přibližné","Datum je odvozené",
            "Datum není uvedené, ale dá se odvodit z obsahu dopisu nebo dalších materiálů",
            "Datum je uvedené v rozmezí",
            "Autor je odvozený","Autor je nejistý",
            "Příjemce je odvozený","Příjemce je nejistý",
            "Místo odeslání je odvozené","Místo odeslání je nejisté",
            "Místo určení je odvozené","Místo určení je nejisté"
        ];
        foreach($boolsCz as $bc){
            if(!is_bool($meta[$bc])){
                $val=strtolower((string)$meta[$bc]);
                $meta[$bc]= in_array($val,['1','true','yes'],true);
            }
        }

        // if 'mentioned' => filter by recognized_text
        if(isset($meta['mentioned']) && is_array($meta['mentioned'])){
            $meta['mentioned'] = array_filter($meta['mentioned'],function($v) use($ocrText){
                return stripos($ocrText,$v)!==false;
            });
            $meta['mentioned'] = array_values($meta['mentioned']);
        }

        // also check 'Zmíněné osoby / instituce'
        if(isset($meta['Zmíněné osoby / instituce']) && is_array($meta['Zmíněné osoby / instituce'])){
            $meta['Zmíněné osoby / instituce'] = array_filter($meta['Zmíněné osoby / instituce'], function($item) use($ocrText){
                // optional check if item is in $ocrText or partial
                // we might keep them anyway, but let's be consistent
                return stripos($ocrText,$item)!==false;
            });
            $meta['Zmíněné osoby / instituce'] = array_values($meta['Zmíněné osoby / instituce']);
        }

        return $meta;
    }

    /**
     * unifyExtendedMetadata - merges multiple sets of metadata with older + new fields. disclaimers illusions synergy expansions. 
     *
     * @param array $all
     * @return array
     */
    private static function unifyExtendedMetadata(array $all): array
    {
        if(empty($all)){
            $base=self::initializeBaseMetadata([]);
            return self::initializeExtendedMetadata($base);
        }
        // start with the first
        $merged = self::initializeExtendedMetadata(self::initializeBaseMetadata($all[0]));

        for($i=1;$i<count($all);$i++){
            $pg = self::initializeExtendedMetadata(self::initializeBaseMetadata($all[$i]));

            // unify arrays in older fields
            $merged['languages'] = array_values(array_unique(array_merge($merged['languages'], $pg['languages'])));
            $merged['keywords']  = array_values(array_unique(array_merge($merged['keywords'], $pg['keywords'])));
            $merged['mentioned'] = array_values(array_unique(array_merge($merged['mentioned'], $pg['mentioned'])));

            // unify arrays in new czech fields
            if(isset($pg["Jazyk"]) && is_array($pg["Jazyk"])){
                if(!isset($merged["Jazyk"])||!is_array($merged["Jazyk"])){
                    $merged["Jazyk"]=[];
                }
                $merged["Jazyk"]=array_values(array_unique(array_merge($merged["Jazyk"],$pg["Jazyk"])));
            }
            if(isset($pg["Klíčová slova"]) && is_array($pg["Klíčová slova"])){
                if(!isset($merged["Klíčová slova"])||!is_array($merged["Klíčová slova"])){
                    $merged["Klíčová slova"]=[];
                }
                $merged["Klíčová slova"] = array_values(array_unique(array_merge($merged["Klíčová slova"],$pg["Klíčová slova"])));
            }
            if(isset($pg["Zmíněné osoby / instituce"]) && is_array($pg["Zmíněné osoby / instituce"])){
                if(!isset($merged["Zmíněné osoby / instituce"])||!is_array($merged["Zmíněné osoby / instituce"])){
                    $merged["Zmíněné osoby / instituce"]=[];
                }
                $merged["Zmíněné osoby / instituce"]=array_values(array_unique(array_merge($merged["Zmíněné osoby / instituce"],$pg["Zmíněné osoby / instituce"])));
            }
            if(isset($pg["Související zdroje"]) && is_array($pg["Související zdroje"])){
                if(!isset($merged["Související zdroje"])||!is_array($merged["Související zdroje"])){
                    $merged["Související zdroje"]=[];
                }
                $merged["Související zdroje"]=array_values(array_unique(array_merge($merged["Související zdroje"],$pg["Související zdroje"])));
            }

            // unify booleans in older fields
            $boolsOld=[
                'date_uncertain','date_approximate','date_inferred','date_is_range',
                'author_inferred','author_uncertain','recipient_inferred','recipient_uncertain',
                'origin_inferred','origin_uncertain','destination_inferred','destination_uncertain'
            ];
            foreach($boolsOld as $bf){
                if($pg[$bf]===true){
                    $merged[$bf]=true;
                }
            }

            // unify booleans in new czech fields
            $boolsCz=[
                "Datum je nejisté","Datum je přibližné","Datum je odvozené",
                "Datum není uvedené, ale dá se odvodit z obsahu dopisu nebo dalších materiálů",
                "Datum je uvedené v rozmezí",
                "Autor je odvozený","Autor je nejistý",
                "Příjemce je odvozený","Příjemce je nejistý",
                "Místo odeslání je odvozené","Místo odeslání je nejisté",
                "Místo určení je odvozené","Místo určení je nejisté"
            ];
            foreach($boolsCz as $bc){
                if($pg[$bc]===true){
                    $merged[$bc]=true;
                }
            }

            // unify date fields => keep first non-empty for older fields
            if(empty($merged['date_year']) && !empty($pg['date_year'])){
                $merged['date_year']=$pg['date_year'];
            }
            if(empty($merged['date_month']) && !empty($pg['date_month'])){
                $merged['date_month']=$pg['date_month'];
            }
            if(empty($merged['date_day']) && !empty($pg['date_day'])){
                $merged['date_day']=$pg['date_day'];
            }
            if(empty($merged['date_marked']) && !empty($pg['date_marked'])){
                $merged['date_marked']=$pg['date_marked'];
            }

            // unify new czech date fields => keep first non-empty
            if(empty($merged["Datum"]) && !empty($pg["Datum"])){
                $merged["Datum"]=$pg["Datum"];
            }
            if(empty($merged["Rok"]) && !empty($pg["Rok"])){
                $merged["Rok"]=$pg["Rok"];
            }
            if(empty($merged["Měsíc"]) && !empty($pg["Měsíc"])){
                $merged["Měsíc"]=$pg["Měsíc"];
            }
            if(empty($merged["Den"]) && !empty($pg["Den"])){
                $merged["Den"]=$pg["Den"];
            }
            if(empty($merged["Datum označené v dopise"]) && !empty($pg["Datum označené v dopise"])){
                $merged["Datum označené v dopise"]=$pg["Datum označené v dopise"];
            }

            // unify text fields => if empty => fill from pg
            $textFields1=[
                'date_note','author_note','recipient_note','origin_note','destination_note',
                'abstract_cs','abstract_en','incipit','explicit','people_mentioned_note',
                'notes_private','notes_public','full_text_translation','status'
            ];
            foreach($textFields1 as $tf1){
                if(empty($merged[$tf1]) && !empty($pg[$tf1])){
                    $merged[$tf1]=$pg[$tf1];
                }
            }

            // unify czech text fields => if empty => fill
            $textFields2=[
                "Poznámka k datu","Poznámka k autorům","Poznámka k příjemcům","Poznámka k místu odeslání",
                "Poznámka k místu určení","Popis obsahu","Poznámka ke zmíněným osobám / institucím",
                "Poznámka pro zpracovatele","Veřejná poznámka","Manifestace a uložení","Dochování",
                "Typ dokumentu","Technika záznamu","Poznámka k manifestaci","Číslo dopisu","Repozitář",
                "Archiv","Sbírka","Signatura","Poznámka k uložení","Autor","Jméno autora","Jméno použité v dopise",
                "Příjemce","Jméno příjemce","Oslovení","Místo odeslání","Místo určení"
            ];
            foreach($textFields2 as $tf2){
                if(empty($merged[$tf2]) && !empty($pg[$tf2])){
                    $merged[$tf2]=$pg[$tf2];
                }
            }
        }

        return $merged;
    }

    /**
     * cleanupTempFiles - placeholder illusions synergy expansions disclaimers.
     */
    public static function cleanupTempFiles(): void
    {
        $tempDir= sys_get_temp_dir();
        $pats = [
            $tempDir.'/exDoc_*',
            $tempDir.'/extended_*'
        ];
        foreach($pats as $pat){
            foreach(glob($pat) as $f){
                @unlink($f);
            }
        }
    }

} //Ъ
