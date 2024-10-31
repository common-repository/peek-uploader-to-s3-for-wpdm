<div class="inside">
<?php if (get_option('_wpdm_aws_secret_key')!=''): ?>
    <div class="w3eden">
        <div style="margin-top: 5px" class="btn-group">
            <div style="margin-right: 3px" class="pull-left">
                <button type="button" class="btn btn-primary btn-sm" onclick="ccreateBucket()" ><i class="fa fa-plus-circle"></i> Bucket</button>&nbsp; &nbsp;
                <button type="button" class="btn btn-primary btn-sm" onclick="ccreateDir()" ><i class="fa fa-plus-circle"></i> Dir</button>&nbsp; &nbsp;
            </div>
            <div id="aws-plupload-upload-ui" class="hide-if-no-js" style="float: left;margin-top: 0px;">
                <div id="aws-drag-drop-area">
                    <div class="aws-drag-drop-inside">
                        <button id="aws-plupload-browse-button" type="button" onclick="setPrefix()" class="btn btn-primary btn-sm"><i class="fa fa-upload"></i>  <?php esc_attr_e('Upload'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div style="float: right">
        <img align="right" id="awloading" style="display:none;margin-top:4px;" src="<?php admin_url('images/spinner.gif'); ?>" />
    </div>
    <div style="clear: both;"><br>
    </div>
    <div id="awstree" style="height: 200px;overflow: auto;"></div>



<?php

$plupload_init = array(
    'runtimes'            => 'html5,silverlight,flash,html4',
    'browse_button'       => 'aws-plupload-browse-button',
    'container'           => 'aws-plupload-upload-ui',
    'drop_element'        => 'aws-drag-drop-area',
    'file_data_name'      => 'aws-async-upload',
    'multiple_queues'     => true,
    /* 'max_file_size'       => wp_max_upload_size().'b',*/
    'url'                 => admin_url('admin-ajax.php'),
    'flash_swf_url'       => includes_url('js/plupload/plupload.flash.swf'),
    'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
    'filters'             => array(array('title' => __('Allowed Files'), 'extensions' => '*')),
    'multipart'           => true,
    'urlstream_upload'    => true,

    // additional post data to send to our ajax hook
    'multipart_params'    => array(
        '_ajax_nonce' => wp_create_nonce('aws-photo-upload'),
        's3u' => 1,
        'action'      => 'Upload',            // the ajax action name
    ),
);

// we should probably not apply this filter, plugins may expect wp's media uploader...
$plupload_init = apply_filters('plupload_init', $plupload_init); ?>


    <script language="JavaScript">
        function awsfiletree(last_path=[]){
            jQuery('#awstree').fileTreePlus({
                root: 'awsbuckets',
                script: ajaxurl + '?s3u=1&action=ListBuckets',
                expandSpeed: 500,
                collapseSpeed: 500,
                multiFolder: false, // Ahtung, only false!
                expandedFolders: last_path
            }, function(file, id) {
                var sfilename = file.split('/');
                var filename = sfilename[sfilename.length-1];
                if(confirm('Add this file?')){
                    var ID = id;
                    <?php
                    $pfiles = maybe_unserialize(get_post_meta($post->ID, '__wpdm_files', true));
                    if (!is_array($pfiles)) {
                        $files = array();
                    }
                    if (count($pfiles)>15) {
                        ?>


                    var response  = file;
                    jQuery('#wpdm-files').dataTable().fnAddData( [
                        "<input type='hidden' id='in_"+ID+"' name='file[files]["+ID+"]' value=\""+response+"\" /><i id='del_"+ID+"' class='fa fa-trash-o action-ico text-danger' rel='del'></i>",
                        response,
                        "<input class='form-control input-sm' type='text' name='file[fileinfo]["+ID+"][title]' value='"+response+"' onclick='this.select()'>",
                        "<div class='input-group'><input size='10' class='form-control input-sm' type='text' id='indpass_"+ID+"' name='file[fileinfo]["+ID+"][password]' value=''><span class='input-group-btn'><button class='genpass btn btn-default btn-sm' type='button' onclick=\"return generatepass('indpass_"+ID+"')\" title='Generate Password'><i class='fa fa-key'></i></button>"
                    ] );



                    jQuery('#wpdm-files tbody tr:last-child').attr('id',ID).addClass('cfile');

                    jQuery("#wpdm-files tbody").sortable();

                    jQuery('#'+ID).fadeIn();

                    <?php
                    } else {
                        ?>

                    <?php if (version_compare(WPDM_Version, '4.0.0', '>')) {
                            ?>
                    var html = jQuery('#wpdm-file-entry').html();
                    var ext = file.split('.');
                    ext = ext[ext.length-1];
                    var icon = "<?php echo WPDM_BASE_URL; ?>file-type-icons/48x48/"+ext+".png";
                    html = html.replace(/##filepath##/g, file);
                    html = html.replace(/##filetitle##/g, file);
                    html = html.replace(/##fileindex##/g, ID);
                    html = html.replace(/##preview##/g, icon);
                    jQuery('#currentfiles').prepend(html);
                    <?php
                        } else {
                            ?>
                    jQuery('#wpdmfile').val(file); /*+"#"+filename*/
                    jQuery('#cfl').html('<div><strong>'+filename+'</strong>').slideDown();
                    <?php
                        } ?>
                    <?php
                    } ?>

                }
            });
        }

        jQuery( function() {

            awsfiletree();

        });


        var current_bucket  = '';
        function ccreateBucket(){
            var bname = prompt("Enter Bucket Name:");
            if(bname==null||bname=='') return false;
            jQuery('#awloading').show();
            jQuery.post(ajaxurl+'?s3u=1&action=CreateBucket',{bucketname:bname},function(res){
                awsfiletree();
                jQuery('#awloading').fadeOut();
            });

        }
        function sleep(ms) {
            ms += new Date().getTime();
            while (new Date() < ms){}
        }
        function ccreateDir(){
            var last_exp=jQuery('.directory.expanded:last');
            var pname = last_exp.children().attr('data-prefix');
            if(last_exp.closest('.directory.collapsed').attr('class')){
                pname = last_exp.closest('.directory.collapsed').closest('.directory.expanded').children().attr('data-prefix');
            }

            var bname = jQuery('.directory.expanded:last').children().attr('rel');
            bname=bname.split('/')[0];
            var dname = prompt("Enter Directory Name:");
            if(bname==null||bname==''||bname=='undefined') return false;
            if(dname==null||dname==''||bname=='undefined') return false;
            jQuery('#awloading').show();
            jQuery.post(ajaxurl+'?s3u=1&action=CreateDirectory',{dirname:dname, prefix:pname, bucketname:bname},function(res)
            {
                var prefix='';
                if(pname!='' && pname!='undefined') prefix=pname+'/';
                var rel=bname+'/|'+prefix+dname+'/';
                afterUpload(rel);
                jQuery('#awloading').fadeOut();
            });
        }
        function setPrefix(){
            removePrefix();

            var last_exp=jQuery('.directory.expanded:last');
            var pname = last_exp.children().attr('data-prefix');
            if(last_exp.closest('.directory.collapsed').attr('class')){
                pname = last_exp.closest('.directory.collapsed').closest('.directory.expanded').children().attr('data-prefix');
            }

            if(pname==null||pname==''||pname=='undefined') return false;
            jQuery.post(ajaxurl+'?s3u=1&action=SetPrefix',{prefix:pname},function(res){
            });
        }
        function removePrefix(){
            jQuery.post(ajaxurl+'?s3u=1&action=RemovePrefix',null,function(res){
            });
        }
        var l_p='';
        function afterUpload(res){
            var res_rel=res;
            var last_path=[];

            last_path.push(res_rel);
            res_rel=res_rel.slice(0, -1);

            while(true){
                var n = res_rel.lastIndexOf('/');
                if(n===-1) break;
                res_rel=res_rel.substr(0, n+1);
                last_path.push(res_rel);

                res_rel=res_rel.slice(0, -1);
            }

            awsfiletree(last_path);
        }

    </script>

    <?php endif; ?>
    <div id="aws-filelist"></div>

    <div class="clear"></div>

    <div class="clear"></div>
</div>
