<div class="mb-3">
    <a href="<?= home_url('blekastad/letters-add'); ?>" class="btn btn-lg btn-primary">Přidat nový dopis</a>
    <button type="button" class="btn btn-lg btn-secondary" @click="exportLetters('bl_letter')" id="export">Exportovat</button>
</div>

<div id="datatable-letters">
    <v-client-table :data="tableData" :columns="columns" :options="options"  v-if="tableData">
        <ul slot="edit" slot-scope="props" class="list-unstyled">
            <li>
                <a :href="'<?= home_url('blekastad/letters-add/?edit='); ?>' + props.row.id">Upravit</a>
            </li>
            <li>
                <a :href="'<?= home_url('blekastad/letters-media/?l_type=bl_letter&letter='); ?>' + props.row.id">Obrazové přílohy</a>
            </li>
            <li>
                <a :href="'<?= home_url('letter-preview/?l_type=bl_letter&letter='); ?>' + props.row.id">Náhled</a>
            </li>
            <li>
                <a :href="'#delete-' + props.row.id" @click="deleteLetter(props.row.id)">Odstranit</a>
            </li>
        </ul>
        <span slot="date" slot-scope="props">
            {{ props.row.year + '/' + props.row.month  + '/' + props.row.day }}
         </span>
         <span slot="author" slot-scope="props">
             <ul class="list-unstyled">
                <li v-for="author in props.row.author"> {{ author }}</li>
             </ul>
          </span>
          <span slot="recipient" slot-scope="props">
              <ul class="list-unstyled">
                 <li v-for="recipient in props.row.recipient"> {{ recipient }}</li>
              </ul>
           </span>

           <span slot="origin" slot-scope="props">
               <ul class="list-unstyled">
                  <li v-for="o in props.row.origin"> {{ o }}</li>
               </ul>
            </span>

            <span slot="dest" slot-scope="props">
                <ul class="list-unstyled">
                   <li v-for="d in props.row.dest"> {{ d }}</li>
                </ul>
             </span>
    </v-client-table>
</div>
