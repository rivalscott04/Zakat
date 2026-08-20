import {
  ClassicEditor as CKClassicEditor,
  Essentials,
  Bold,
  Italic,
  Underline,
  Link,
  List,
  Paragraph,
  Heading,
  BlockQuote,
  Table,
  TableToolbar,
  Undo,
} from "ckeditor5";
import "ckeditor5/ckeditor5.css";

class ClassicEditor extends CKClassicEditor {
  public static override builtinPlugins = [
    Essentials,
    Bold,
    Italic,
    Underline,
    Link,
    List,
    Paragraph,
    Heading,
    BlockQuote,
    Table,
    TableToolbar,
    Undo,
  ];

  public static override defaultConfig = {
    licenseKey: "GPL",
    toolbar: {
      items: [
        "undo",
        "redo",
        "|",
        "heading",
        "|",
        "bold",
        "italic",
        "underline",
        "|",
        "link",
        "bulletedList",
        "numberedList",
        "blockQuote",
        "insertTable",
      ],
    },
  };
}

export default ClassicEditor;
