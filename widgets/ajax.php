<script type="text/javascript">
    jQuery(document).ready(function($){
        // create the uploader andg pass the config from above
        var uploader = new plupload.Uploader(<?php echo json_encode($plupload_init); ?>);

        // checks if browser supports drag and drop upload, makes some css adjustments if necessary
        uploader.bind('Init', function(up){
            var uploaddiv = jQuery('#aws-plupload-upload-ui');
            uploaddiv.find('input[type="file"]').attr('onclick', 'setPrefix()');

            if(up.features.dragdrop){
                uploaddiv.addClass('drag-drop');
                jQuery('#aws-drag-drop-area')
                    .bind('dragover.wp-uploader', function(){ uploaddiv.addClass('drag-over'); })
                    .bind('dragleave.wp-uploader, drop.wp-uploader', function(){ uploaddiv.removeClass('drag-over'); });

            }else{
                uploaddiv.removeClass('drag-drop');
                jQuery('#aws-drag-drop-area').unbind('.wp-uploader');
            }
        });

        uploader.init();


        // a file was added in the queue
        var files_count=0;
        uploader.bind('FilesAdded', function(up, files){
            //var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);
            files_count=files.length;
            jQuery('#awloading').fadeIn();

            plupload.each(files, function(file){
                jQuery('#aws-filelist').append(
                    '<div class="file" id="' + file.id + '"><b>' +

                        file.name + '</b> (<span>' + plupload.formatSize(0) + '</span>/' + plupload.formatSize(file.size) + ') ' +
                        '<div class="fileprogress"></div></div>');
            });

            up.refresh();
            up.start();
        });

        uploader.bind('UploadProgress', function(up, file) {

            jQuery('#' + file.id + " .fileprogress").width(file.percent + "%");
            jQuery('#' + file.id + " span").html(plupload.formatSize(parseInt(file.size * file.percent / 100)));
        });


        // a file was uploaded
        uploader.bind('FileUploaded', function(up, file, response) {
//            awsfiletree(); // del here and move to afterUpload()
            jQuery('#awloading').fadeOut();
            // this is your ajax response, update the DOM with it or something...
            //console.log(response);
            //response
            jQuery('#' + file.id ).remove();
            var d = new Date();
            var ID = d.getTime();
            response = response.response;
            var nm = response;

            if(--files_count <= 0) afterUpload(response);
            if(files_count>0) sleep(100); //ahtung

            //if(response.length>20) nm = response.substring(0,7)+'...'+response.substring(response.length-10);
            //jQuery('#file_source_url').val(response);
        });

    });

</script>