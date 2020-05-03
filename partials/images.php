<div id="media-handler">

    <div v-if="error" class="alert alert-warning">
        Nepodařilo se načíst požadovaný dopis.
    </div>

    <div v-if="!error">
        <h3>
            Dopis:
            <a :href="url" target="_blank" v-html="title"></a>
        </h3>

        <div class="section mb-5">
            <h4>Nahrát obrazové přílohy</h4>
            <div id="drag-drop-area"></div>
        </div>

        <div class="section mb-5">
            <h4 v-if="images.length > 1">Upravit nahrané obrazové přílohy</h4>

            <div class="text-right" v-if="images.length > 1">
                <span v-if="!orderMode" class="text-info pointer" @click="orderMode = true">Seřadit</span>
                <span v-if="orderMode">
                    <span class="text-warning pointer" @click="orderMode = false; getImages()">Zrušit</span>
                    /
                    <span v-if="orderMode" class="text-info pointer" @click="saveImagesOrder">Uložit</span>
                </span>
            </div>

            <draggable v-if="orderMode" v-model="images" :options="{handle:'.handle', animation: 150, ghostClass: 'bg-light'}" ref="dnd">
                <div v-for="(image, index) in images" :key="index" class="media my-3 border border-primary">
                    <div class="media-body p-2 d-flex justify-content-between align-items-center">
                        <img class="mr-3" :src="image.img.thumb" :alt="image.description" width="64px" height="64px">
                        <span>
                            {{ image.description }}
                        </span>
                        <span class="oi oi-move display-4 handle" style="line-height:1"></span>
                    </div>
                </div>
            </draggable>

            <ul id="media-list" class="list-unstyled" v-if="!orderMode">
                <li v-for="(image, index) in images" :key="index" class="media p-2 my-3 border border-primary flex-wrap">
                    <img class="mr-3" :src="image.img.thumb" :alt="image.description">
                    <div class="media-body pb-2">
                        <ul class="list-unstyled">
                            <li class="text-info pointer" @click="openModal(image)">
                                <span class="oi oi-eye mr-1"></span>Zobrazit
                            </li>
                            <li class="text-danger pointer" @click="deleteImage(image.id)">
                                <span class="oi oi-trash mr-1"></span>Odstranit
                            </li>
                        </ul>
                        <form class="mt-3" style="max-width:400px">
                            <div class="form-group mb-2">
                                <label for="description" class="mb-1">Popisek</label>
                                <textarea v-model="image.description" type="text" name="description" class="form-control form-control-sm"></textarea>
                            </div>
                            <div class="form-group mb-2">
                                <label for="status" class="mb-1">Viditelnost</label>
                                <select v-model="image.status" class="form-control form-control-sm" name="status">
                                    <option value="private">Soukromé</option>
                                    <option value="inherit">Veřejné</option>
                                </select>
                            </div>
                            <button @click="editImageMetadata(image)" type="button" name="button" class="btn btn-sm btn-primary">
                                Uložit změny
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <div v-if="modal.visibility" class="modal d-flex">
        <div class="modal-dialog" role="document" style="overflow: auto;max-width: 100%;width: auto !important;display: inline-block;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" @click="closeModal()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <img v-if="modal.src" :src="modal.src" class="w-100">
                </div>
            </div>
        </div>
    </div>

</div>
