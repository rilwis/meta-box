( function ( $ ) {
	'use strict';
	
	if (wp.media) {
		wp.media.view.Modal.prototype.on('ready', function(data) {
			wp.media.view.Modal.prototype.on( "open", function() {
				tinymce_init();
			});
			wp.media.view.Modal.prototype.on( "close", function() {
				tinymce_init();
			});
			let lastUrl = location.href; 
			new MutationObserver(() => {
				const url = location.href;
				if (url !== lastUrl) {
					// console.log('URL changed!', location.href);
					lastUrl = url;
					tinymce_init();
				}
				}).observe(document, {subtree: true, childList: true});
		});
		
	}

	function tinymce_init(){
		tinymce.remove();
		tinymce.init(
			{
				selector: "textarea.rwmb-wysiwyg",
				mode : "exact",
				elements : 'pre-details',
				theme: "modern",
				skin: "lightgray",
				menubar : false,
				statusbar : false,
				toolbar: [
					"bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | undo redo"
				],
				plugins : "paste",
				paste_auto_cleanup_on_paste : true,
				paste_postprocess : function( pl, o ) {
					o.node.innerHTML = o.node.innerHTML.replace( /&nbsp;+/ig, " " );
				},
				setup: function (editor) {
					editor.on('change', function () {
						editor.save();
						var id = location.href.substring(location.href.indexOf('=') + 1,location.href.length);
						var value = tinyMCE.activeEditor.getContent();
						var field = editor.id;
						//console.log(id + ' ' + field+ ' ' +value);
						var ajax_url = location.href.substring(0,location.href.indexOf('/')) + 'admin-ajax.php';
						wp.ajax.post( "save_media_fields", {id: id, field: field, value: value} )
						.done(function(response) {
							//console.log(response);
							//alert(response);
						});
					});
				}
			}
		);
		$('.wp-switch-editor.switch-html').click();
		$('.wp-switch-editor.switch-tmce').click();
	}

} )( jQuery );
