<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/">
    <responseDate>{{ now()->toIso8601String() }}</responseDate>
    <ListMetadataFormats>
        @foreach ($metadataFormats as $format)
        <metadataFormat>
            <metadataPrefix>{{ $format['prefix'] }}</metadataPrefix>
            <schema>{{ $format['schema'] }}</schema>
            <metadataNamespace>{{ $format['namespace'] }}</metadataNamespace>
        </metadataFormat>
        @endforeach
    </ListMetadataFormats>
</OAI-PMH>
