<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/">
    <responseDate>{{ now()->toIso8601String() }}</responseDate>
    <Identify>
        <repositoryName>{{ $repositoryName }}</repositoryName>
        <baseURL>{{ $baseURL }}</baseURL>
        <protocolVersion>{{ $protocolVersion }}</protocolVersion>
        <adminEmail>{{ $adminEmail }}</adminEmail>
        <earliestDatestamp>{{ $earliestDatestamp }}</earliestDatestamp>
        <deletedRecord>{{ $deletedRecord }}</deletedRecord>
        <granularity>{{ $granularity }}</granularity>
    </Identify>
</OAI-PMH>
