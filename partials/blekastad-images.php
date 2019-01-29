<div id="media-handler">

    <div v-if="error" class="alert alert-warning">
        Nepodařilo se načíst požadovaný dopis.
    </div>

    <div v-if="!error">
        <h3>Dopis: {{ title }}</h3>
        <div class="section mb-5">
            <h4>Nahrát obrazové přílohy</h4>
            <div id="drag-drop-area"></div>
        </div>
        <div class="section mb-5">
            <h4>Upravit nahrané obrazové přílohy</h4>
            <div id="media-list"></div>
        </div>
    </div>

</div>
