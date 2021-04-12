    <div class="container py-3 mt-5 footer">
        <div class="row justify-content-center">
            <div class="col">
                <a href="mailto:pachlova@lib.cas.cz?cc=knavcr@lib.cas.cz&subject=hiko%20administrace" class="d-none">Něco se pokazilo</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap.native@2.0.15/dist/bootstrap-native-v4.min.js"></script>

    <?php if (current_user_can('administrator')) : ?>
        <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <?php else : ?>
        <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.min.js"></script>
    <?php endif; ?>

    <?php if (is_page_template('page-templates/page-images.php')) : ?>
        <script src="https://cdn.jsdelivr.net/npm/uppy@1.0.0/dist/uppy.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.9.0/Sortable.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/vuedraggable@2.20.0/dist/vuedraggable.umd.min.js"></script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@0.18.0/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tabulator-tables@4.9.3/dist/js/tabulator.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
    <script src="<?= get_template_directory_uri() . '/assets/vendor/tagify/tagify.min.js' ?>"></script>
    <script src="<?= get_template_directory_uri() . '/assets/vendor/tagify/dragsort.js' ?>"></script>
    <script src="<?= get_template_directory_uri() . '/assets/dist/custom.min.js?v=' . filemtime(get_template_directory() . '/assets/dist/custom.min.js'); ?>"></script>

    <?php wp_footer(); ?>
</body>

</html>
