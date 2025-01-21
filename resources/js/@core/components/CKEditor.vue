<template>
  <div class="main-container">
    <div
      ref="editorContainerElement"
      class="editor-container editor-container_classic-editor editor-container_include-word-count"
    >
      <div class="editor-container__editor">
        <div ref="editorElement">
          <Ckeditor
            v-if="editor && config"
            :model-value="modelValue"
            :editor="editor"
            :config="config"
            @input="updateContent"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, useTemplateRef } from 'vue'
import { Ckeditor } from '@ckeditor/ckeditor5-vue'
import {
  ClassicEditor,
  Autoformat,
  AutoImage,
  Autosave,
  BlockQuote,
  Bold,
  CloudServices,
  Essentials,
  Heading,
  ImageBlock,
  ImageCaption,
  ImageInline,
  ImageInsertViaUrl,
  ImageResize,
  ImageStyle,
  ImageTextAlternative,
  ImageToolbar,
  ImageUpload,
  Indent,
  IndentBlock,
  Italic,
  Link,
  LinkImage,
  List,
  ListProperties,
  MediaEmbed,
  Mention,
  Paragraph,
  PasteFromOffice,
  Table,
  TableCaption,
  TableCellProperties,
  TableColumnResize,
  TableProperties,
  TableToolbar,
  TextTransformation,
  TodoList,
  Underline,
  WordCount,
  FontColor,
  FontBackgroundColor,
  Alignment,
  FindAndReplace,
  FontSize,
  Base64UploadAdapter,
} from 'ckeditor5'

import 'ckeditor5/ckeditor5.css'

const props = defineProps({
  modelValue: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(['update:modelValue'])

const LICENSE_KEY = 'GPL'

const editorWordCount = useTemplateRef('editorWordCountElement')

const isLayoutReady = ref(false)
const editor = ClassicEditor

const config = computed(() => {
  if (!isLayoutReady.value) {
    return null
  }

  return {
    toolbar: {
      items: [
        'heading',
        'imageInsert',
        '|',
        'bold',
        'italic',
        'underline',
        'bulletedList',
        '|',
        'blockQuote',
        'insertTable',
        'alignment',
        'fontBackgroundColor',
        'fontColor',
        'fontSize',
      ],
      shouldNotGroupWhenFull: true,
    },
    plugins: [
      Autoformat,
      AutoImage,
      Alignment,
      FontBackgroundColor,
      FontColor,
      FontSize,
      FindAndReplace,
      Autosave,
      BlockQuote,
      Bold,
      CloudServices,
      Essentials,
      Heading,
      ImageBlock,
      ImageCaption,
      ImageInline,
      ImageInsertViaUrl,
      ImageResize,
      ImageStyle,
      ImageTextAlternative,
      ImageToolbar,
      ImageUpload,
      Indent,
      IndentBlock,
      Italic,
      Link,
      LinkImage,
      List,
      ListProperties,
      MediaEmbed,
      Mention,
      Paragraph,
      PasteFromOffice,
      Table,
      TableCaption,
      TableCellProperties,
      TableColumnResize,
      TableProperties,
      TableToolbar,
      TextTransformation,
      TodoList,
      Underline,
      WordCount,
      Base64UploadAdapter,
    ],
    heading: {
      options: [
        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
        { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
        { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
        { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
        { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' },
      ],
    },
    image: {
      toolbar: [
        'toggleImageCaption',
        'imageTextAlternative',
        '|',
        'imageStyle:inline',
        'imageStyle:wrapText',
        'imageStyle:breakText',
        '|',
        'resizeImage',
      ],
    },
    licenseKey: LICENSE_KEY,
    link: {
      addTargetToExternalLinks: true,
      defaultProtocol: 'https://',
      decorators: {
        toggleDownloadable: {
          mode: 'manual',
          label: 'Downloadable',
          attributes: { download: 'file' },
        },
      },
    },
    list: {
      properties: { styles: true, startIndex: true, reversed: true },
    },
    mention: {
      feeds: [
        {
          marker: '@',
          feed: [],
        },
      ],
    },
    placeholder: 'Type or paste your content here!',
    table: {
      contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'],
    },
  }
})

onMounted(() => {
  isLayoutReady.value = true
})

// function onReady(editor) {
//   [...editorWordCount.value.children].forEach(child => child.remove())
//
//   const wordCount = editor.plugins.get('WordCount')
//
//   editorWordCount.value.appendChild(wordCount.wordCountContainer)
// }

function updateContent(newValue) {
  emit('update:modelValue', newValue)
}
</script>
