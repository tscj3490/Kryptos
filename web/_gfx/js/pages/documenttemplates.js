widget.push({
    class: 'ckeditor-documenttemplates',
    scripts: {
        sync: [
            '/assets/plugins/ckeditor.4.4/ckeditor.js',
            '/_gfx/js/plugins/ckeditor.js'
        ]
    },
    initElement: function(tg) {
        var blocks = {};
        $.each(documenttemplateForms, function() {
            blocks[this.name] = {
                name: this.name,
                label: this.name,
                url: '/home/ajax-get-section/name/documenttemplate_form?context[form_id]=' + this.id + '&context[type]=result'
            };
        });
console.log(blocks);
        var editor = CKEDITOR.replace(tg.get(0), {
            filebrowserBrowseUrl: '/elfinder/browser',
            fullPage: false,
            entities_additional: '',
            filebrowserImageBrowseUrl: '/elfinder/browser' + '?type=image',
            removeDialogTabs: 'link:upload;image:upload',
            toolbarGroups: [
                { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
                { name: 'colors' },
                { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
                { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align' ] },
                { name: 'links' },
                { name: 'insert' },
                { name: 'styles' },
                { name: 'tools' },
                { name: 'others' },
                { name: 'document', groups: [ 'mode' ] },
                { name: 'kryptoscustomtags', groups: [ 'editkryptoscustomtag' ] }
            ],
            extraPlugins: 'kryptoscustomtags',
            removeButtons: 'Flash,Iframe,Print,Preview,Save,NewPage',
            extraAllowedContent: 'div[data-*]',
            contentsCss: '/inspections/editor-get-css',
            height:600,

            kryptoscustomtags: {
                elements: {
                    blocks: blocks                }
            }
        });

        ckeditorLiveSaveIntegration(editor, tg);
        ckeditorAutohide(editor);
    }
});
