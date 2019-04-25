    <div class="footer container mt-5 py-3">
        <div class="row justify-content-center">
            <div class="col">
                <a href="mailto:pachlova@lib.cas.cz?cc=knavcr@lib.cas.cz&subject=hiko%20administrace" class="d-none">Něco se pokazilo</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap.native@2.0.15/dist/bootstrap-native-v4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="https://unpkg.com/axios@0.18.0/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-tables-2@1.4.70/dist/vue-tables-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
    <script src="https://unpkg.com/vue-select@latest"></script>
    <script src="https://transloadit.edgly.net/releases/uppy/v0.29.1/dist/uppy.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/Vue.Draggable/2.17.0/vuedraggable.min.js"></script>
    <script src="<?= get_template_directory_uri(); ?>/assets/dist/custom.min.js?v=<?= filemtime(get_template_directory() . '/assets/dist/custom.min.js'); ?>"></script>

    <?php wp_footer(); ?>
</body>

</html>
