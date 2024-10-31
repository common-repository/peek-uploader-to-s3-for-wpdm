<?php
/*
Plugin Name: Peak Uploader to S3 for WPDM
Description: This plugin extends Wordpress Download Manager plugin and let to upload files to Amazon S3. <br/><strong><a href="https://wordpress.org/plugins/download-manager/">Wordpress Download Manager</a> should be installed and active</strong> to use this plugin.
Version: 1.0
Author: Peak Technologies Ltd
Author URI: http://peaktechnologies.ru
*/

require_once dirname(__FILE__) . '/includes/aws/aws-autoloader.php';
require_once dirname(__FILE__) . '/functions.php';
require_once dirname(__FILE__) . '/includes/class.s3u.php';
require_once dirname(__FILE__) . '/includes/S3.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Common\Credentials\Credentials;
use Aws\CloudFront\CloudFrontClient;
use Aws\CloudFront\Exception\CloudFrontException;

class PeakS3Uploader
{
    protected $s3;
    protected $cf;

    public function __construct()
    {
        $s3_settings = [
            'version' => 'latest',
            'region'  => 'us-east-1', // us-east-1 for distribution work
            'credentials' => [
                'key' => get_option('_wpdm_aws_access_key'),
                'secret' => get_option('_wpdm_aws_secret_key')
            ]
        ];
        $this->s3 = new S3Client($s3_settings);

        $s3_settings['version'] = '2017-03-25';
        $this->cf = new CloudFrontClient($s3_settings);

        if (!defined('WPDM_CLOUD_STORAGE')) {
            define('WPDM_CLOUD_STORAGE', 1);
        }

        if (defined("WPDM_Version")) {
            add_action('admin_menu', array($this, 'Menu'));
            add_filter('wpdm_meta_box', array($this, 's3UploaderWidget'));

            add_filter('wpdm_cloud_storage_settings', array($this, 's3Access'));

            add_action('wp_ajax_s3UploadAllFiles', array($this, 's3UploadAllFiles'));

            add_action('wp_ajax_CreateDirectory', array($this, 'CreateDirectory'));
            add_action('wp_ajax_CreateBucket', array($this, 'CreateBucket'));
            add_action('wp_ajax_ListBuckets', array($this, 'ListBuckets'));
            add_action('wp_ajax_GetBucket', array($this, 'GetBucket'));
            add_action('wp_ajax_Upload', array($this, 'Upload'));

            add_action('wp_ajax_SetPrefix', array($this, 'SetPrefix'));
            add_action('wp_ajax_RemovePrefix', array($this, 'RemovePrefix'));

            add_action('wp_ajax_S3U_Update', array($this, 'S3U_Update'));
            add_action('wp_ajax_S3U_Remove', array($this, 'S3U_Remove'));
            add_action('wp_ajax_S3U_TransferAcceleration_Toggle', array($this, 'S3U_TransferAcceleration_Toggle'));
            add_action('wp_ajax_S3U_DistributionCreate', array($this, 'S3U_DistributionCreate'));
            add_action('wp_ajax_S3U_Distribution_Toggle', array($this, 'S3U_Distribution_Toggle'));

            add_action('s3u_distributions_list_update', array($this, 'distributions_list_update'));

            add_action('s3u_update_hooks_init', array($this, 's3u_update_hooks_init'));

            add_action('s3u_s3_option', array($this, 'S3U_Option'));
            add_action('s3u_s3_distribution', array($this, 'S3U_Distribution'));
            add_action('s3u_s3_transferacceleration', array($this, 'S3U_TransferAcceleration'));

            add_action('admin_enqueue_scripts', array($this, 's3Scripts'), 15);
        }
        else {
            add_action('admin_init',  array($this, 'checkDependencyWPDMPlugin'));
        }
    }


    // function that register and enqueues scripts
    public function s3Scripts()
    {
        if (get_post_type()=='wpdmpro'||in_array(wpdm_query_var('page'), array('s3-uploader'))) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-form');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-slider');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-timepicker', WPDM_BASE_URL.'assets/js/jquery-ui-timepicker-addon.js', array('jquery','jquery-ui-core','jquery-ui-datepicker','jquery-ui-slider'));

            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
            wp_enqueue_script('media-upload');
            wp_enqueue_media();

            wp_enqueue_script('jquery-choosen', plugins_url('/download-manager/assets/js/chosen.jquery.min.js'), array('jquery'));
            wp_enqueue_style('choosen-css', plugins_url('/download-manager/assets/css/chosen.css'));
            wp_enqueue_style('jqui-css', plugins_url('/download-manager/assets/jqui/theme/jquery-ui.css'));

            wp_enqueue_script('wpdm-bootstrap', plugins_url('/download-manager/assets/bootstrap/js/bootstrap.min.js'), array('jquery'));
            wp_enqueue_script('wpdm-admin', plugins_url('/download-manager/assets/js/wpdm-admin.js'), array('jquery'));
            wp_enqueue_style('font-awesome', WPDM_BASE_URL.'assets/font-awesome/css/font-awesome.min.css');
            wp_enqueue_style('wpdm-bootstrap', plugins_url('/download-manager/assets/bootstrap/css/bootstrap.css'));
            wp_enqueue_style('wpdm-bootstrap-theme', plugins_url('/download-manager/assets/bootstrap/css/bootstrap-theme.min.css'));
            wp_enqueue_style('wpdm-admin-styles', plugins_url('/download-manager/assets/css/admin-styles.css'));

            wp_enqueue_script('wpdm-s3u-tree', plugins_url().'/s3-uploader/assets/js/jqueryFileTreePlus.js');
            wp_enqueue_style('s3u-page', plugins_url().'/s3-uploader/assets/css/s3-uploader-page.css');
        }
    }

    // 
    public function Menu()
    {
        add_submenu_page('edit.php?post_type=wpdmpro', 'S3-Uploader &lsaquo; Download Manager', 'S3-Uploader', WPDM_MENU_ACCESS_CAP, 's3-uploader', array($this, 's3UploaderPage'));
    }
    public function s3UploaderPage()
    {
        $this->s3u_update_hooks_init();

        include "tpls/s3-uploader-page/s3-uploader-head.php";

        include "tpls/s3-uploader-page/s3-upload-form.php";
        include "tpls/s3-uploader-page/s3-options-form.php";

        include "tpls/s3-uploader-page/s3-uploader-footer.php";
    }

    public function s3UploaderWidget($metaboxes)
    {
        $metaboxes['amazons3uploader'] = array('title'=>'S3-Uploader Widget', 'callback'=>array($this, 's3UploaderWidgetHTML'), 'position'=>'side');
        return $metaboxes;
    }
    public function s3UploaderWidgetHTML()
    {
        include "widgets/modules/s3-uploader-widget.php";
        include "widgets/ajax.php";
    }

    public function s3Access()
    {
        include "tpls/s3-access-page.php";
    }

    public function s3UploadAllFiles()
    {
        $bucket = $_POST['bucketname'];
        $pathways=glob(UPLOAD_DIR.'*');

        $errors='';
        foreach ($pathways as $path) {
            $file_name=substr($path, strrpos($path, '/')+1);
            if (!is_writable($path)) {
                $errors.='Permission denied ('.$file_name.'): '.$path.'<br />';
                continue;
            }

            try {
                if (is_dir($path)) {
                    $file_name .= '/';
                    $file = '';
                } else {
                    $file = fopen($path, 'r+');
                }

                $this->s3->upload($bucket, $file_name, $file);
            } catch (Exception $e) {
                $errors.=$e.'<br />';
            }
        }

        die($errors!='' ? $errors : 'ok');
    }


    public function ListBuckets()
    {
        if (!isset($_GET['s3u'])) {
            return;
        }

        if (isset($_POST['dir']) && $_POST['dir']!='awsbuckets') {
            $this->GetBucket();
            die();
        }

        $buckets = $this->s3->listBuckets();

        $buckets = $buckets['Buckets'];

        echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
        // All dirs
        if (!isset($_SESSION['current_bucket']) || $_SESSION['current_bucket']=='') {
            $_SESSION['current_bucket'] = $buckets[0]['Name'];
        }
        foreach ($buckets as $bucket) {
            $bucket = $bucket['Name'];
            $cx = $_SESSION['current_bucket']==$bucket?'expanded':'collapsed';
            if ($_SESSION['current_bucket']!=''&&$_SESSION['current_bucket']==$bucket && 1==2) {
                $stree = "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
                $files = $this->GetBucket($bucket);
                if (is_array($files)) {
                    foreach ($files as $name => $file_info) {
                        $ext = preg_replace('/^.*\./', '', $name);
                        $furl = "http://{$bucket}.s3.amazonaws.com/".$name;
                        $stree .= "<li class=\"file ext_$ext\"><a id=\"".uniqid()."\" href=\"#\" rel=\"" . $furl . "\">" . $file_info['name'] . "</a></li>";
                    }
                }

                $stree .= "</ul>";
            } else {
                $stree = "";
            }
            echo "<li class=\"directory {$cx}\"><a id=\"".uniqid()."\" href=\"#\" rel=\"" . $bucket . "/\">" . $bucket . "</a>{$stree}</li>";
        }
        echo "</ul>";

        die();
    }

    public function GetBucket()
    {
        if (!isset($_GET['s3u'])) {
            return;
        }

        $dir = explode('|', urldecode($_POST['dir']));
        $bucket = $dir[0];
        $prefix = isset($dir[1])?$dir[1]:'';
        $_SESSION['current_bucket'] = $bucket;

        echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";

        $files = $this->s3->listObjects(array('Bucket'=>rtrim($bucket, '/'), 'Prefix' => $prefix));
        $files = ($files['Contents']);
        $list = array();
        if (is_array($files)) {
            foreach ($files as $file_info) {
                $folder = str_replace_first($prefix, '', $file_info['Key']);
                if (strstr($folder, '/')) {
                    $folder = explode("/", $folder);
                    $nprefix = $folder[0];
                    $file_info['type'] = 'd';
                    if (!array_key_exists($nprefix, $file_info) && $nprefix !='') {
                        $list[$nprefix] = $file_info;
                    }
                } else {
                    $file_info['type'] = 'f';
                    if ($folder !='') {
                        $list[$folder] = $file_info;
                    }
                }
            }

            foreach ($list as $nprefix => $file_info) {
                $ext = preg_replace('/^.*\./', '', $file_info['Key']);
                $ext = 'file ext_'.$ext;

                if ($file_info['Owner']['DisplayName'] != 's3-log-service') {
                    $bucketname = rtrim($bucket, '/');

                    $host=$this->S3U_LinkManager($bucketname);
                    $furl='http://'.$host.'/'.$file_info['Key'];

                    if ($file_info['type'] == 'f') {
                        echo "<li class=\"$ext\"><a id=\"" . uniqid() . "\" href=\"#\" rel=\"" . $furl . "\">" . $nprefix ."</a></li>";
                    } else {
                        echo "<li class=\"directory collapsed\"><a id=\"" . uniqid() . "\" href=\"#\"  rel='$bucket|{$prefix}{$nprefix}/' data-bucket='{$bucket}' data-prefix=\"{$prefix}{$nprefix}\">" . $nprefix . "</a></li>";
                    }
                }
            }
        }

        echo "</ul>";
        die();
    }

    public function CreateBucket()
    {
        $bucket_name=$_POST['bucketname'];
        if (preg_match('/^[a-z0-9][a-z0-9-]{1,61}$/', $bucket_name)==0) {
            die('Bad name for bucket. Without space, use only a-z letters, 1-9 and "-" symbol');
        }
        try {
            $this->s3->createBucket(array('Bucket' => $bucket_name));
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }

        $this->make_bucket_public($bucket_name);

        die('ok');
    }

    public function SetPrefix()
    {
        die($_SESSION['current_prefix'] = $_POST['prefix']);
    }
    public function RemovePrefix()
    {
        die($_SESSION['current_prefix']='');
    }
    public function Upload()
    {
        $pathToFile = $_FILES['aws-async-upload']['tmp_name'];
        $file = fopen($pathToFile, 'r+');
        $filename =  $_FILES['aws-async-upload']['name'];

        $bucket = rtrim($_SESSION['current_bucket'], '/');
        $prefix='';
        if ($_SESSION['current_prefix'] && $_SESSION['current_prefix']!='') {
            $prefix = $_SESSION['current_prefix'].'/';
        }

        $this->s3->upload($bucket, $prefix.$filename, $file);

        die($bucket.'/|'.$prefix);
    }

    public function CreateDirectory()
    {
        $bucket = $_POST['bucketname'];
        $dir = $_POST['dirname'];
        $prefix = '';
        if (isset($_POST['prefix']) && $_POST['prefix']!='/' && $_POST['prefix']!='undefined') {
            $prefix=$_POST['prefix'].'/';
        }


        try {
            $this->s3->putObject(array(
                'Bucket' => $bucket,
                'Key' => "{$prefix}{$dir}/",
                'Body' => ""
            ));

            die($bucket.'/|'.$prefix);
        } catch (S3Exception $e) {
            die($e->getMessage());
        }
    }


    private function make_bucket_public($bucket_name)
    {
        $json='{
                    "Version":"2012-10-17",
                    "Statement":[{
                    "Sid":"AllowPublicRead",
                        "Effect":"Allow",
                        "Principal": {
                            "AWS": "*"
                            },
                        "Action":["s3:GetObject"],
                        "Resource":["arn:aws:s3:::'.$bucket_name.'/*"
                        ]
                    }
                    ]
                }';
        $request=array(
            'Bucket' => $bucket_name,
            'Policy' => $json
        );
        $this->s3->putBucketPolicy($request);
    }
    private function remove_bucket_publicity($bucket_name)
    {
        $request=array(
            'Bucket' => $bucket_name
        );

        $this->s3->deleteBucketPolicy($request);
    }

    // S3-Uploader page

    public function S3U_LinkManager($bucket_name)
    {
        $link=$bucket_name.'.s3.amazonaws.com';
        $options=array('cloudfront', 'acceleration');
        $options=array_fill_keys($options, false);

        foreach (array_keys($options) as $option) {
            $opt=new Option($option);
            $options[$option]=$opt->get();
        }

        if ($options['cloudfront']) {
            $dist = new Distribution($bucket_name);
            $dist->get();
            if (!empty($dist->last_result) && $dist->last_result['enabled']) {
                return $dist->last_result['domain'];
            }
        }
        if ($options['acceleration']) {
            $ta = new Acceleration($bucket_name);
            $ta->get();
            if ($ta->last_result) {
                return $bucket_name.'.s3-accelerate.amazonaws.com';
            }
        }

        return $link;
    }

    public function distributions_list_update()
    {
        $dist_list = $this->cf->listDistributions()->toArray();
        $dist_list = $dist_list['DistributionList']['Items'];
        $new_data=array();

        $buckets_with_dist = array();
        foreach ($dist_list as $dist_value) {
            $origin = $dist_value['Origins']['Items'][0]['DomainName'];
            $bucket = substr($origin, 0, strpos($origin, '.'));
            $buckets_with_dist[] = $bucket;

            $new_data[]=array(
                'name' => $bucket,
                'value' => array(
                    'id' => $dist_value['Id'],
                    'domain' => $dist_value['DomainName'],
                    'enabled' => $dist_value['Enabled'] == true,
                    'status' => $dist_value['Status'],
                )
            );
        }

        $this->S3U_Update($new_data, 'Distribution');

        $buckets = $this->s3->listBuckets()["Buckets"];
        $all_buckets = array_map(function ($buckets) {
            return $buckets['Name'];
        }, $buckets);
        $buckets_without_dist = array_diff($all_buckets, $buckets_with_dist);

        foreach ($buckets_without_dist as $b_without_d) {
            $dist_to_remove = new Distribution($b_without_d);
            $dist_to_remove->remove();
        }
    }

    public function S3U_DistributionCreate()
    {
        S3::setAuth(get_option('_wpdm_aws_access_key'), get_option('_wpdm_aws_secret_key'));
        $answer = S3::createDistribution($_POST['bucket']);

//        $this->s3u_update_hooks_init();
        die(json_encode($answer));
    }

    public function S3U_Distribution_Toggle()
    {
        $bucket = $_POST['bucket'];
        $dist = new Distribution($bucket);
        $dist_data = $dist->get();

        $xml_as_array = $this->cf->getDistributionConfig(['Id' => $dist_data['id']])->toArray();
        $xml_as_array['Id'] = $dist_data['id'];
        $xml_as_array['IfMatch'] = $xml_as_array['ETag'];
        $xml_as_array['DistributionConfig']['Enabled'] = !$dist_data['enabled'];
        $answer = $this->cf->updateDistribution($xml_as_array);

//        $this->s3u_update_hooks_init();
        die(json_encode($answer));
    }

    public function S3U_Distribution($bucket_data)
    {
        $dist=new Distribution($bucket_data[0]);
        $dist_data=$dist->get();
        if (!empty($dist_data)) {
            if ($dist_data['status']=='Deployed') {
                echo '<kbd style="font-weight: 700; margin-right: 10px;" class="bg-info text-info distribution_link"><span>' . $dist_data['status'] . '</span>: '.$dist_data['domain'].'</kbd>';
            } else {
                echo '<kbd style="font-weight: 700; margin-right: 10px;" class="bg-warning text-warning distribution_link"><span>' . $dist_data['status'] . '</span>: '.$dist_data['domain'].'</kbd>';
            }

            if ($dist_data['enabled']) {
                echo '<button id="' . $bucket_data[0] . '" name="s3u_distribution_button" style="" class="btn btn-danger btn-sm" type="button"><i style="display: none; margin-right:5px" class="fa fa-cog fa-spin"></i>Not distribute</button>';
            } else {
                echo '<button id="' . $bucket_data[0] . '" name="s3u_distribution_button" style="" class="btn btn-success btn-sm" type="button"><i style="display: none; margin-right:5px" class="fa fa-cog fa-spin"></i>Distribute</button>';
            }
        } else {
            echo '<kbd style="font-weight: 700; margin-right: 10px;" class="bg-info text-info distribution_link"><span>Not created</span></kbd>';
            echo '<button id="'.$bucket_data[0].'" name="s3u_distribution_create_button" style="" class="btn btn-success btn-sm" type="button"><i style="display: none; margin-right:5px" class="fa fa-cog fa-spin"></i>Create distribution</button>';
        }
    }
    public function S3U_Option($field_name)
    {
        $opt=new Option($field_name);
        if ($opt->get()) {
            echo 'checked';
        }
    }

    public function S3U_Remove()
    {
        $field=$_POST['field'];
        $model=$_POST['model'];

        $r=new $model($field);
        $r->remove();

//        die(json_encode($r->errors()));
    }
    public function S3U_Update($fields='', $model='')
    {
        $errors=array();
        $return=false;
        if (empty($fields) || empty($model)) {
            $fields=$_POST['fields'];
            $model=$_POST['model'];
        } else {
            $return=true;
        }

        foreach ($fields as $field) {
            $errors=array_merge($errors, $this->s3u_update_req($model, $field));
        }

        if ($return) {
            return $errors;
        } else {
            die(json_encode($errors));
        }
    }
    private function s3u_update_req($model, $field)
    {
        $m=new $model($field);
        $m->update();

        return $m->was_updated() ? array() : $m->errors();
    }
    private function s3u_update_hooks_init($condition = true)
    {
        if ($condition) {
            $this->distributions_list_update();
        }
    }

    // S3-Accelerate

    public function S3U_TransferAcceleration($bucket_name)
    {
        $ta=new Acceleration($bucket_name);

        if ($ta->get() == 'true') {
            echo '<button id="'.$bucket_name.'" name="s3u_acceleration_button" style="display: none" class="btn btn-danger btn-sm" type="button"><i style="display: none; margin-right:5px" class="fa fa-cog fa-spin"></i>Decelerate</button>';
        } else {
            echo '<button id="'.$bucket_name.'" name="s3u_acceleration_button" style="display: none" class="btn btn-success btn-sm" type="button"><i style="display: none; margin-right:5px" class="fa fa-cog fa-spin"></i>Accelerate</button>';
        }
    }
    public function S3U_TransferAcceleration_Toggle()
    {
        $bucket=$_POST['bucket'];
        $errors=array();
        $status=$this->s3u_ta_status($bucket) == 'Suspended' ?
            'Enabled' : 'Suspended';

        $request=array(
            'AccelerateConfiguration' => array('Status' => $status),
            'Bucket' => $bucket
        );
        $this->s3->putBucketAccelerateConfiguration($request);

        if ($this->s3u_ta_status($bucket) != $status) {
            $errors[]='Error: Status has not been updated';
        } else {
            $errors=$this->S3U_Update(array(['name' => $bucket, 'value' => $status == 'Enabled']), 'Acceleration');
//            $this->s3u_update_hooks_init();
        }

        die(json_encode($errors));
    }
    private function s3u_ta_status($bucket_name)
    {
        $request=array(
            'Bucket' => $bucket_name
        );
        return $this->s3->getBucketAccelerateConfiguration($request)->get('Status');
    }
    public function checkDependencyWPDMPlugin()
    {
        if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'download-manager/download-manager.php' ) ) {
            add_action( 'admin_notices', array($this, 'wpdmDependencyNotice'));

            deactivate_plugins( plugin_basename( __FILE__ ) );

            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }
    }
    public function wpdmDependencyNotice() {
        ?><div class="error"><p>Sorry, but S3 Uploader Plugin requires the <a href="https://wordpress.org/plugins/download-manager/" target="_blank">Wordpress Downloads Manager</a> plugin to be installed and active.</p></div><?php
    }
}

new PeakS3Uploader();
