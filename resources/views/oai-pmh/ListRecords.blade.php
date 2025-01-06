<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/">
    <responseDate>{{ now()->toIso8601String() }}</responseDate>
    <ListRecords>
        @foreach ($records as $record)
        <record>
            <header>
                <identifier>{{ $record['identifier'] }}</identifier>
                <datestamp>{{ $record['datestamp'] }}</datestamp>
            </header>
            <metadata>
                <oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
                           xmlns:dc="http://purl.org/dc/elements/1.1/"
                           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                           xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/
                           http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
                    <dc:title>{{ $record['metadata']['title'] }}</dc:title>
                    <dc:creator>{{ $record['metadata']['creator'] }}</dc:creator>
                    <dc:subject>{{ $record['metadata']['subject'] }}</dc:subject>
                    <dc:description>{{ $record['metadata']['description'] }}</dc:description>
                    <dc:date>{{ $record['metadata']['date'] }}</dc:date>
                    <dc:type>{{ $record['metadata']['type'] }}</dc:type>
                    <dc:format>{{ $record['metadata']['format'] }}</dc:format>
                    <dc:identifier>{{ $record['metadata']['identifier'] }}</dc:identifier>
                    <dc:language>{{ $record['metadata']['language'] }}</dc:language>
                    <dc:rights>{{ $record['metadata']['rights'] }}</dc:rights>
                    <dc:relation>Origin: {{ $record['metadata']['relation']['origin'] }}</dc:relation>
                    <dc:relation>Destination: {{ $record['metadata']['relation']['destination'] }}</dc:relation>
                </oai_dc:dc>
            </metadata>
        </record>
        @endforeach
    </ListRecords>
</OAI-PMH>
