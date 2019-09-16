/**
 * @file
 * CodeMirror toolbar.
 */

(function ($, Drupal, baseUrl) {

  'use strict';

  /**
   * Creates a toolbar.
   *
   * @param {object} editor
   *   The editor instance.
   * @param {object} options
   *   The editor options.
   */
  Drupal.codeMirrorToolbar = function (editor, options) {
    editor.$toolbar = $('<div class="cme-toolbar"/>')
      .prependTo($(editor.getWrapperElement()));
    createButtons(editor, options);
    createModeSelect(editor, options);
  };

  /**
   * Creates editor buttons.
   */
  function createButtons(editor, options) {
    $('<div class="cme-buttons"/>')
      .prependTo(editor.$toolbar)
      .load(options.buttonsBaseUrl);

    options.buttons.forEach(function (button) {
      // @TODO: Add title attribute.
      $('<svg data-cme-button="' + button + '" class="cme-button"><use xlink:href="#icon-' + button + '"></use></svg>')
        .appendTo(editor.$toolbar);
    });
    editor.$toolbar.find('[data-cme-button="shrink"]').hide();

    function setFullScreen(state) {
      editor.setOption('fullScreen', state);
      editor.$toolbar.find('svg[data-cme-button="enlarge"]').toggle(!state);
      editor.$toolbar.find('svg[data-cme-button="shrink"]').toggle(state);
    }

    var extraKeys = {
      F11: function (editor) {
        setFullScreen(!editor.getOption('fullScreen'));
      },
      Esc: function () {
        setFullScreen(false);
      }
    };
    editor.setOption('extraKeys', extraKeys);

    var doc = editor.getDoc();

    function createHtmlList(type) {
      var list = '<' + type + '>\n';
      doc.getSelection().split('\n').forEach(function (value) {
        list += '  <li>' + value + '</li>\n';
      });
      list += '</' + type + '>\n';
      doc.replaceSelection(list, doc.getCursor());
    }

    function buttonClickHandler(event) {
      var button = $(event.target).closest('[data-cme-button]').data('cme-button');
      switch (button) {

        case 'bold':
          doc.replaceSelection('<strong>' + doc.getSelection() + '</strong>', doc.getCursor());
          break;

        case 'italic':
          doc.replaceSelection('<em>' + doc.getSelection() + '</em>', doc.getCursor());
          break;

        case 'underline':
          doc.replaceSelection('<u>' + doc.getSelection() + '</u>', doc.getCursor());
          break;

        case 'strike-through':
          doc.replaceSelection('<s>' + doc.getSelection() + '</s>', doc.getCursor());
          break;

        case 'list-numbered':
          createHtmlList('ol');
          break;

        case 'list-bullet':
          createHtmlList('ul');
          break;

        case 'link':
          doc.replaceSelection('<a href="">' + doc.getSelection() + '</a>', doc.getCursor());
          break;

        case 'horizontal-rule':
          doc.replaceSelection('<hr/>', doc.getCursor());
          break;

        case 'undo':
          doc.undo();
          break;

        case 'redo':
          doc.redo();
          break;

        case 'clear-formatting':
          doc.replaceSelection($('<div>' + doc.getSelection() + '</div>').text(), doc.getCursor());
          break;

        case 'enlarge':
          setFullScreen(true);
          break;

        case 'shrink':
          setFullScreen(false);
          break;

      }
    }
    editor.$toolbar.click(buttonClickHandler);
  }

  /**
   * Creates a select list of available modes.
   */
  function createModeSelect(editor, options) {
    if (!$.isEmptyObject(options.modeSelect)) {
      var selectOptions = '';
      for (var key in options.modeSelect) {
        if (options.modeSelect.hasOwnProperty(key)) {
          selectOptions += '<option value="' + key + '">' + options.modeSelect[key] + '</option>';
        }
      }
      $('<select class="cme-mode"/>')
        .append(selectOptions)
        .val(options.mode)
        .change(function () {
          var value = $(this).val();
          editor.setOption('mode', value);
          // Save the value to cookie.
          var modesEncoded = $.cookie('codeMirrorModes');
          var modes = modesEncoded ? JSON.parse(modesEncoded) : {};
          modes[editor.getTextArea().getAttribute('data-drupal-selector')] = value;
          $.cookie('codeMirrorModes', JSON.stringify(modes), { path: baseUrl });
        })
        .appendTo(editor.$toolbar);
    }
  }

}(jQuery, Drupal, drupalSettings.path.baseUrl));
;
/**
 * @file
 * CodeMirror editor behaviors.
 */

(function ($, Drupal, debounce, defaultOptions) {

  'use strict';

  var editors = {};
  Drupal.editors.codemirror_editor = {
    attach: function attach(element, format) {
      editors[element.id] = init(element, format.editorSettings);
    },
    detach: function (element, format, trigger) {
      if (trigger !== 'serialize') {
        editors[element.id].toTextArea(element);
      }
    },
    onChange: function (element, callback) {
      editors[element.id].on('change', debounce(callback, 500));
    }
  };

  var warn = true;
  Drupal.behaviors.codeMirrorEditor = {

    attach: function () {
      var $textAreas = $('textarea[data-codemirror]').once('codemirror-editor');

      // Only check library when at least once CodeMirror textarea presented on 
      // the page.
      if ($textAreas.length && typeof CodeMirror === 'undefined' && warn) {
        alert(Drupal.t('CodeMirror library is not loaded!'));
        warn = false;
        return;
      }

      $.each($textAreas, function (key, textArea) {
        init(textArea);
      });
    },

    detach: function () {
      // CodeMirror tracks form submissions to update textareas but this does
      // not work ajax requests. So we save data manually.
      var $editors = $('.CodeMirror').once('codemirror-editor');
      $.each($editors, function (key, editor) {
        editor.CodeMirror.save();
      });
    }

  };

  /**
   * Initializes CodeMirror editor for a given textarea.
   */
  function init(textArea, options) {

    var $textArea = $(textArea);
    options = options || $textArea.data('codemirror');
    options = jQuery.extend({}, defaultOptions, options);

    // Remove "required" attribute because the textarea is not focusable.
    $textArea.removeAttr('required');

    // Create HTML/Twig overlay mode.
    CodeMirror.defineMode('html_twig', function (config, parserConfig) {
      return CodeMirror.overlayMode(
        CodeMirror.getMode(config, parserConfig.backdrop || 'text/html'),
        CodeMirror.getMode(config, 'twig')
      );
    });

    // Load language mode from cookie if possible.
    var modesEncoded = $.cookie('codeMirrorModes');
    var modes = modesEncoded ? JSON.parse(modesEncoded) : {};
    options.mode = modes[$textArea.data('drupal-selector')] || options.mode;

    // Duplicate line command.
    CodeMirror.keyMap.pcDefault['Ctrl-D'] = function (cm) {
      var currentCursor = cm.doc.getCursor();
      var lineContent = cm.doc.getLine(currentCursor.line);
      CodeMirror.commands.goLineEnd(cm);
      CodeMirror.commands.newlineAndIndent(cm);
      cm.doc.replaceSelection(lineContent.trim());
      cm.doc.setCursor(currentCursor.line + 1, currentCursor.ch);
    };

    // Comment line command.
    CodeMirror.keyMap.pcDefault['Ctrl-/'] = function (cm) {
      cm.toggleComment();
    };

    var editor = CodeMirror.fromTextArea(textArea, {
      // The theme cannot be changed per textarea because this would require
      // loading CSS files for all available themes.
      theme: options.theme,
      lineNumbers: options.lineNumbers,
      mode: options.mode,
      readOnly: options.readOnly,
      foldGutter: options.foldGutter,
      autoCloseTags: options.autoCloseTags,
      styleActiveLine: options.styleActiveLine,
      // The plugin tracks mouseup and keyup events. So no need to poll the
      // editor every 250ms (default delay value).
      autoRefresh: {delay: 3000}
    });

    var $wrapper = $(editor.getWrapperElement());

    // Set helper class to hide cursor when the text area is read only.
    // See https://github.com/codemirror/CodeMirror/issues/1099.
    if (options.readOnly) {
      $wrapper.addClass('cme-readonly');
    }

    // Bubble error class.
    if ($textArea.hasClass('error')) {
      $wrapper.addClass('cme-error');
    }

    if (options.foldGutter) {
      editor.setOption('gutters', ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']);
    }

    editor.setSize(options.width, options.height);
    editor.getScrollerElement().style.minHeight = $textArea.height() + 'px';

    if (options.toolbar) {
      Drupal.codeMirrorToolbar(editor, options);
    }

    return editor;
  }

}(jQuery, Drupal, Drupal.debounce, drupalSettings.codeMirrorEditor));
;
