<template>
  <DefaultField :field="field" :errors="errors" :show-help-text="showHelpText">
    <template #field>
      <div class="o1-flex" v-if="hasValue">
        <div class="o1-flex">
          <template v-if="field.multiple">
            <Draggable v-model="value" item-key="id" class="o1-flex o1-flex-wrap" :disabled="field.readonly">
              <template #item="{ element: mediaItem }">
                <MediaItem
                  :key="mediaItem.id"
                  :mediaItem="mediaItem"
                  :size="24"
                  class="o1-mb-4 o1-mr-4"
                  @contextmenu.stop.prevent="openContextMenu($event, mediaItem)"
                />
              </template>
            </Draggable>
          </template>

          <MediaItem
            v-else-if="!!value"
            class="o1-mb-4"
            :mediaItem="value"
            :size="36"
            @contextmenu.stop.prevent="openContextMenu($event, value)"
          />
        </div>
      </div>

      <Button v-if="!field.readonly" type="button" @click.prevent.stop="openChooseModal">
        {{ __('novaMediaHub.chooseMediaTitle') }}
      </Button>

      <MediaItemContextMenu
        :id="`form-media-hub-field-ctx-menu-${field.attribute}`"
        :showEvent="ctxShowEvent"
        :options="ctxOptions"
        @close="ctxShowEvent = void 0"
        :mediaItem="ctxMediaItem"
        :readonly="field.readonly"
      />

      <ChooseMediaModal
        :field="field"
        :initialSelectedMediaItems="value"
        :show="showChooseModal"
        @close="showChooseModal = false"
        @confirm="mediaItemsSelected"
      />
    </template>
  </DefaultField>
</template>

<script>
import Draggable from 'vuedraggable';
import MediaItem from '../../components/MediaItem';
import ChooseMediaModal from '../../modals/ChooseMediaModal';
import { FormField, HandlesValidationErrors } from 'laravel-nova';
import MediaItemContextMenu from '../../components/MediaItemContextMenu';
import HandlesMediaHubFieldValue from '../../mixins/HandlesMediaHubFieldValue';
import { Button } from 'laravel-nova-ui';

export default {
  components: { Draggable, MediaItem, ChooseMediaModal, MediaItemContextMenu, Button },
  mixins: [FormField, HandlesValidationErrors, HandlesMediaHubFieldValue],
  props: ['resourceName', 'resourceId', 'field'],

  data: () => ({
    showChooseModal: false,
    showMediaViewModal: false,

    ctxShowEvent: void 0,
    ctxOptions: [],
    ctxMediaItem: void 0,
  }),

  created() {
    this.ctxOptions = [
      { name: this.__('novaMediaHub.contextViewEdit'), action: 'view' },
      { name: this.__('novaMediaHub.contextDownload'), action: 'download' },
    ];
  },

  methods: {
    mediaItemsSelected(mediaItems) {
      this.value = mediaItems;
      this.showChooseModal = false;
      this.$emit('input');
    },

    fill(formData) {
      if (this.value && this.value.length) {
        this.value.map((mediaItem, index) => {
          formData.append(`${this.field.attribute}[${index}]`, mediaItem.id);
        });
      } else if (this.value && this.value.id) {
        formData.append(this.field.attribute, this.value.id);
      } else {
        formData.append(this.field.attribute, '');
      }
    },

    openContextMenu(event, mediaItem) {
      this.ctxMediaItem = mediaItem;
      this.ctxShowEvent = event;
    },

    openChooseModal() {
      this.ctxShowEvent = void 0;
      this.showChooseModal = true;
    },
  },

  computed: {
    hasValue() {
      return this.field.multiple ? !!this.value?.length : !!this.value;
    },
  },
};
</script>
