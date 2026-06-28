<?php

namespace ICT\Core\Message;

/* * ***************************************************************
 * Copyright © 2015 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\CoreException;
use ICT\Core\Corelog;
use ICT\Core\DB;
use ICT\Core\Token;
use ICT\Core\Message;
use ICT\Core\Activity;
use ICT\Core\Session;
use ICT\Core\User;
use NcJoes\OfficeConverter\OfficeConverter;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Random;

class Document extends Message
{

  /** @const */
  protected static $table = 'document';
  protected static $primary_key = 'document_id';
  protected static $fields = array(
    'document_id',
    'tenant_id',
    'name',
    'file_name',
    'type',
    'description',
    'ocr',
    'pages',
    'size_x',
    'size_y',
    'quality',
    'resolution_x',
    'resolution_y'
  );
  protected static $read_only = array(
    'document_id',
    'size_x',
    'size_y',
    'resolution_x',
    'resolution_y',
    'pages'
  );

  /**
   * @property-read integer $document_id
   * @var integer
   */
  protected $document_id = NULL;

  /**
   * @property string $file_name
   * @see Document::set_file_name()
   * @var string 
   */
  protected $file_name = NULL;
  protected $file_source = NULL;
  protected $aSource = array();

  /** @var integer */
  public $tenant_id = NULL;

  /** @var string */
  public $name = NULL;


  /**
   * @property-read string $link
   * @see Document::get_link()
   */

  /** @var string */
  protected $type = NULL;

  /** @var string */
  public $description = NULL;

  /** @var string */
  public $ocr = NULL;

  /** @var string */
  public $direction = '/';

  /**
   * @property-read integer $pages
   * @var integer
   */
  protected $pages = NULL;

  /**
   * @var integer
   */
  public $transmission = NULL;

  /**
   * @property-read integer $size_x
   * @var integer
   */
  protected $size_x = NULL;

  /**
   * @property-read integer $size_y
   * @var integer
   */
  protected $size_y = NULL;

  /**
   * @property integer $quality
   * Quality of document
   * @param string("basic", "standard", "fine", "super", "superior", "ultra") $quality
   */
  public $quality = 'standard';

  /**
   * @property-read integer $resolution_x
   * all possible values are
   * * 100
   * * 200 or 204
   * * 400 or 408
   * @var integer
   */
  protected $resolution_x = 204;

  /**
   * @property-read integer $resolution_y
   * other possible values are
   * for width : 100
   * * 100
   * for width : 200 or 204
   * * 98 or 100
   * * 196 or 200
   * * 391 or 400
   * for width : 400 or 408
   * * 391 or 400
   * @var integer
   */
  protected $resolution_y = 98;

  /**
   * Default mime type for this message type, when no type is available
   * @var string
   */
  public static $media_default = 'application/pdf';

  /**
   * Array of all supported file extensions along with mime types as keys
   * @var array $media_supported
   */
  public static $media_supported = array(
    'pdf'  => 'application/pdf',
    'tif'  => 'image/tiff',
    'tiff' => 'image/x-tiff',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/x-citrix-jpeg',
    /*
      'txt'  => 'text/plain',
      'text' => 'text/plain',
      'htm'  => 'text/htm',
      'html' => 'text/html',
      */
    // office files
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'odp'  => 'application/vnd.oasis.opendocument.presentation',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'odt'  => 'application/vnd.oasis.opendocument.text',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ods'  => 'application/vnd.oasis.opendocument.spreadsheet'
  );


  public function __construct($document_id = NULL, $transmission = NULL)
  {
    if ($transmission) {
      $this->transmission = 1;
    }
    $this->document_id = $document_id;
    $this->transmission = $transmission;
    parent::__construct($document_id);
    $this->message_id = &$this->document_id; // Assign by reference will keep both variable same
  }

  public static function search($aFilter = array())
  {
    $aDocument = array();
    $totalrows = null;
    $from_str = self::$table . " as d";
    $aWhere = array();
    $limitSql = '';
    $pageIndex = isset($aFilter['pageIndex']) ? (int)$aFilter['pageIndex'] : 0;
    $pageSize = isset($aFilter['pageSize']) ? (int)$aFilter['pageSize'] : 0;
    foreach ($aFilter as $search_field => $search_value) {
      switch ($search_field) {
        case 'document_id':
        case 'tenant_id':
          $aWhere[] = "d.$search_field = $search_value";
          break;
        case 'name':
        case 'type':
        case 'description':
          $aWhere[] = "d.$search_field LIKE '%$search_value%'";
          break;

        case 'user_id':
        case 'created_by':
          $aWhere[] = "d.created_by = '$search_value'";
          break;
        case 'totalrows':
          $totalrows = 1;
          break;
        case 'before':
          $aWhere[] = "d.date_created <= $search_value";
          break;
        case 'after':
          $aWhere[] = "d.date_created >= $search_value";
          break;
        case 'email':
        case 'username':
          $aWhere[] = "u.$search_field = '$search_value'";
          $from_str .= ' LEFT JOIN usr u ON d.created_by=u.usr_id';
          break;
        case ($pageIndex > 0 && $pageSize > 0):
          $offset = ($pageIndex - 1) * $pageSize;
          $limit  = $pageSize * 5;
          $limitSql = " LIMIT $limit OFFSET $offset ";
          break;
        case ($pageSize > 0 && $pageIndex == 0):
          $offset = 0;
          $limit  = $pageSize * 5;
          $limitSql = " LIMIT $limit OFFSET $offset ";
          break;
      }
    }
    if (!empty($aWhere)) {
      $from_str .= ' WHERE ' . implode(' AND ', $aWhere);
    }
    $query = "SELECT d.document_id, d.tenant_id, d.name, d.file_name, d.type, d.pages, d.description, d.ocr FROM " . $from_str . " ORDER BY d.document_id DESC " . $limitSql;
    if ($totalrows) return parent::totalrows($query);
    Corelog::log("document search with $query", Corelog::ERROR, array('aFilter' => $aFilter));
    $result = DB::query('document', $query);
    while ($data = mysqli_fetch_assoc($result)) {
      $aDocument[] = $data;
    }
    return $aDocument;
  }

  protected function load()
  {
    $query = "SELECT * FROM document WHERE document_id = $this->document_id";
    $result = DB::query(self::$table, $query, array('document_id' => $this->document_id));
    $data = mysqli_fetch_assoc($result);
    if ($data) {
      $this->document_id = $data['document_id'];
      $this->tenant_id = $data['tenant_id'];
      $this->name = $data['name'];
      $this->file_name = $data['file_name'];
      $this->aSource = json_decode($data['file_source']);
      $this->file_source = \ICT\Core\path_array_to_string($this->aSource);
      $this->type = $data['type'];
      $this->description = $data['description'];
      $this->ocr = $data['ocr'];
      $this->pages = $data['pages'];
      $this->size_x = $data['size_x'];
      $this->size_y = $data['size_y'];
      $this->quality = $data['quality'];
      $this->resolution_x = $data['resolution_x'];
      $this->resolution_y = $data['resolution_y'];
      $this->user_id = $data['created_by'];
      $this->has_token = $data['has_token'] ? TRUE : FALSE;
      Corelog::log("Document loaded name: $this->name", Corelog::CRUD);
    } else {
      throw new CoreException('404', 'Document not found');
    }
  }


  public function save()
  {
    $this->token_detect();
    $data = array(
      'document_id' => $this->document_id,
      'tenant_id' => $this->tenant_id,
      'name' => $this->name,
      'file_name' => $this->file_name,
      'file_source' => json_encode($this->aSource),
      'type' => $this->type,
      'description' => $this->description,
      'size_x' => $this->size_x,
      'size_y' => $this->size_y,
      'quality' => $this->quality,
      'resolution_x' => $this->resolution_x,
      'resolution_y' => $this->resolution_y,
      'pages' => $this->pages,
      'has_token' => $this->has_token ? '1' : 0
    );

    if (isset($data['document_id']) && !empty($data['document_id'])) {
      // update existing record
      $result = DB::update(self::$table, $data, 'document_id');
      Corelog::log("Document updated: $this->document_id", Corelog::CRUD);
      $activity = new Activity();
      $activity->userlogs("Update Document Name:($this->name) -ID:($this->document_id)");
    } else {
      // add new
      $result = DB::update(self::$table, $data, false);
      $this->document_id = $data['document_id'];
      Corelog::log("New Document created: $this->document_id", Corelog::CRUD);
      $activity = new Activity();
      $activity->userlogs("Create New Document Name:($this->name) -ID:($this->document_id)");
    }
    return $result;
  }

  public function delete()
  {
    Corelog::log("Document delete", Corelog::CRUD);
    if (DB::delete(self::$table, 'document_id', $this->document_id)) {
      exec("rm -rf '$this->file_name'");
      $user_id = Session::get_instance()->user->user_id;
      if ($user_id > 0) {
        $activity = new Activity();
        $activity->userlogs("Delete Document Name:[ $this->name ] -ID:[$this->document_id]");
      }
      if ($user_id == -1) {
        $activity = new Activity();
        $activity->userlogs("Retention Delete Document [ $this->name ] -ID:[$this->document_id]");
      }
      return true;
    }
    return false;
  }


  protected function set_file_name($file_path)
  {
    global $path_data;
    $oSession = Session::get_instance();
    $user_id =  $oSession->user->user_id;
    $tenant_id = $oSession->user->tenant_id;
    // Create temporary file name
    $file_name = 'document_' . $user_id . '_';
    $file_name .= DB::next_record_id($file_name);

    $aFile = \ICT\Core\path_string_to_array($file_path);
    if (!empty($this->file_name) && in_array($this->file_name, $aFile)) {
      $file_type = $this->type;
      $encrypted_file = $this->file_name;
      $pos = array_search($pdf_file, $aFile);
      unset($aFile[$pos]);
    } else {
      // Generate encrypted file path and type
      $file_type = empty($this->type) ? 'aes' : $this->type;

      mkdir("$path_data/document/tenant_$tenant_id/user_$user_id/inbound", 0777, true);
      $encrypted_file = $path_data . DIRECTORY_SEPARATOR . "document/tenant_$tenant_id/user_$user_id" . $this->direction . $file_name . '.aes';
    }
    $pdf_main_file = $path_data . DIRECTORY_SEPARATOR . 'document' . DIRECTORY_SEPARATOR . $file_name . '.pdf';
    foreach ($aFile as $file) {
      $temp_file_name = 'temp_file_name' . mt_rand(100000, 999999);
      $aType = explode('.', $file);
      $file_type = empty($this->type) ? end($aType) : $this->type;
      $file_type = strtolower($file_type);
      if ($file_type != 'pdf') {
        // Create temporary pdf file path
        $pdf_file = $path_data . DIRECTORY_SEPARATOR . 'document' . DIRECTORY_SEPARATOR . $temp_file_name . '.pdf';
        $pdfFile = $this->create_pdf($file, $file_type, $pdf_file);
        // Remove file
        exec("rm -rf '$file'");
      } else $pdfFile = $file;
      if ($pdfFile) {
        $temp_pdfs[] = $pdfFile;
      }
    }
    if (count($temp_pdfs) > 1) {
      $merge_cmd = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=" . escapeshellarg($pdf_main_file) . " " . implode(' ', array_map('escapeshellarg', $temp_pdfs));
      exec($merge_cmd);
    } elseif (count($temp_pdfs) === 1) {
      copy($temp_pdfs[0], $pdf_main_file);
    }
    $this->encrypt_file($pdf_main_file, $encrypted_file);
    $this->type = $file_type;
    $this->file_name = $encrypted_file;
  }

  protected function get_file_name()
  {
    global $path_cache;
    if (!$this->token_applied) {
      // AES-encrypted files must be decrypted and converted to TIFF before fax use
      if (pathinfo($this->file_name, PATHINFO_EXTENSION) === 'aes') {
        $tiff_file = tempnam($path_cache, "fax_tiff_") . '.tif';
        $this->create_tiff($this->file_name, 'aes', $tiff_file, $this->quality);
        chmod($tiff_file, 0744);
        return $tiff_file;
      }
      return $this->file_name; // return existing tiff file, if we didn't have applied the tokens
    }
    $personalized_tiff = tempnam($path_cache, "personalized_tiff_") . '.tif'; // please note we are using random file name, to avoid, overwriting the existing one
    foreach ($this->aSource as $file) {
      $this->create_tiff($file, $this->type, $personalized_tiff, $this->quality); // it will append new tiff file into $tiff_file
    }
    chmod($personalized_tiff, 0744); // set permissions, so freeswitch can read it
    return $personalized_tiff;
  }

  protected function set_quality($quality)
  {
    switch ($quality) {
      case 'basic':
        $this->resolution_x = 100;
        $this->resolution_y = 98;  // or 100
        break;
      case 'standard':
        $this->resolution_x = 204; // or 200
        $this->resolution_y = 98;  // or 100
        break;
      case 'fine':
        $this->resolution_x = 204; // or 200
        $this->resolution_y = 196; // or 200
        break;
      case 'super':
        $this->resolution_x = 204; // or 200
        $this->resolution_y = 391; // or 400
        break;
      case 'superior':
        $this->resolution_x = 300;
        $this->resolution_y = 300;
        break;
      case 'ultra':
        $this->resolution_x = 408; // or 400
        $this->resolution_y = 391; // or 400
        break;
      default:
        break;
    }
  }

  public function create_tiff($sourceFile, $type, $targetFile, $quality = 'standard')
  {
    $this->pages = 0;
    $this->size_y = 0;
    $this->size_x = 0;

    $this->set_quality($quality);

    $infos = '';
    $pdfFile = self::create_pdf($sourceFile, $type);
    exec(\ICT\Core\sys_which('pdfinfo', '/usr/bin') . " '$pdfFile'", $infos);
    foreach ($infos as $info_row) {
      $matches = array();
      if (preg_match('/^Pages:\s*([0-9.]*)$/', $info_row, $matches)) {
        $this->pages = $matches[1];
      } else if (preg_match('/^Page size:\s*([0-9.]*) x ([0-9.]*)/', $info_row, $matches)) {
        $this->size_y = round($matches[1]);
        $this->size_x = round($matches[2]);
      }
    }

    // some time simple pdf to tiff conversion can create problem in fax sending, I don't why? probably we need a raster image then vector
    // but ps to tiff conversion can solve this problem
    // also it will fix the image size and orientation issue
    global $path_etc;
    $config = "$path_etc/postscript/rotate.ps";
    $resolution_string = $this->resolution_x . "x" . $this->resolution_y;
    $cmd = \ICT\Core\sys_which('gs', '/usr/bin') . " -q -dNOPAUSE -dBATCH -P- -dSAFER -sDEVICE=ps2write -r$resolution_string -sOutputFile='$pdfFile.ps' -c save pop -f '$config' '$pdfFile'";
    exec($cmd);

    //$cmd = "convert -quiet -density -threshold 85% 150 $sourceFile -shave 65x65 -colorspace rgb -quality 100 -resample 320 $targetFile";
    // for monochrome (black/wite) color
    //$mono = ' -c "<< /HalftoneMode 1 >> setuserparams"';
    //$mono = ' -dDITHER=300 -Ilib stocht.ps -c "{ dup .9 lt { pop 0 } if } settransfer"';
    $mono = ' -dDITHER=300 -c "{ dup .85 lt { pop 0 } if } settransfer"'; // ref https://bugs.ghostscript.com/show_bug.cgi?id=694762
    $cmd  = \ICT\Core\sys_which('gs', '/usr/bin') . " -dBATCH -dNOPAUSE -sDEVICE=tiffg3 -sOutputFile='$targetFile.tmp' $mono -f '$pdfFile.ps'";
    Corelog::log("Converting source image into fax support tiff", Corelog::CRUD, $cmd);
    exec($cmd);
    //exec("rm -rf '$sourceFile'");
    // -a for append and -t for tiles i.e pages in correct sequence like A1,A2,A3,B1,B2,C1,C2,C3
    $cmd = \ICT\Core\sys_which('tiffcp', '/usr/bin') . " -x -a '$targetFile.tmp' '$targetFile'";
    exec($cmd);
    exec("rm -rf '$targetFile.tmp'");

    return $this->pages;
  }

  public function create_pdf($sourceFile, $type = '', $pdfFile = '')
  {
    if (empty($pdfFile)) {
      $t_dir = pathinfo($sourceFile, PATHINFO_DIRNAME);
      $t_file = pathinfo($sourceFile, PATHINFO_FILENAME).'.pdf';
      $pdfFile = $t_dir . '/' . $t_file;
    }
    switch ($type) {
      case 'aes':
        $this->decrypt_file($sourceFile, $pdfFile);
        break;
      case 'tif':
      case 'tiff':
        Corelog::log("Converting tif/tiff into pdf", Corelog::CRUD);
        $pdfFile = "$sourceFile.pdf";
        $cmd = \ICT\Core\sys_which('tiff2pdf', '/usr/bin') . " -o $pdfFile -z $sourceFile";
        exec($cmd);
        //exec("rm -rf '$sourceFile'");
        break;
      case 'htm':
      case 'html':
        Corelog::log("Converting htm/html into pdf", Corelog::CRUD);
        $pdfFile = "$sourceFile";
        //TODO: add html file support
        break;
      case 'txt':
      case 'text':
        Corelog::log("Converting txt/text into pdf", Corelog::CRUD);
        $pdfFile = "$sourceFile.pdf";
        $cmd = \ICT\Core\sys_which('textfmt', 'usr/sbin') . " $sourceFile > $sourceFile.ps";
        //$cmd = "/usr/local/bin/uniprint -hsize 0 -size 9 -in $sourceFile.pdf -out $sourceFile.ps";
        exec($cmd);
        //exec("rm -rf '$sourceFile'");
        $cmd = \ICT\Core\sys_which('ps2pdf', '/usr/bin') . " $sourceFile.ps $pdfFile";
        exec($cmd);
        //exec("rm -rf '$sourceFile.ps'");
        break;
      case 'png':
      case 'jpg':
      case 'jpeg':
        Corelog::log("Converting png/jpg into pdf", Corelog::CRUD);
        $pdfFile = "$sourceFile.pdf";
        $cmd = \ICT\Core\sys_which('convert', '/usr/bin') . " $sourceFile $pdfFile";
        exec($cmd);
        //exec("rm -rf '$sourceFile'");
        break;
      case 'pptx':
      case 'ppt':
      case 'odp':
      case 'docx':
      case 'doc':
      case 'odt':
      case 'xlsx':
      case 'xls':
      case 'ods':
        Corelog::log("Converting office document into pdf", Corelog::CRUD);
        global $path_cache;
        $home_dir = $path_cache; // home directory is required to save / read office configurations
        $target_dir = pathinfo($sourceFile, PATHINFO_DIRNAME); // use source directory as target directory
        $include_path = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'; // required to include all required jvm
        $office_binary = \ICT\Core\sys_which('libreoffice', '/usr/bin');
        $result = exec("export HOME=$home_dir && export PATH=$include_path && $office_binary --headless --convert-to pdf $sourceFile  --outdir $target_dir");
        $pdfFile = preg_replace('/\.' . pathinfo($sourceFile, PATHINFO_EXTENSION) . '$/', '.pdf', $sourceFile);
        break;
      default:
        Corelog::log("Unknown file type assume it as pdf", Corelog::CRUD);
        $pdfFile = "$sourceFile";
        break;
    }
    return $pdfFile;
  }

  public function get_pdf_file()
  {
    return $this->create_pdf($this->file_name, $this->type);
  }

  public function token_detect()
  {
    $this->has_token = FALSE; // start with false, and turn to true if token is detected in any file
    foreach ($this->aSource as $currentFile) {
      $file_content = $this->token_read_content($currentFile);
      $tokenList    = Token::find_token($file_content);
      //$this->token_remove_content($currentFile);
      if (!empty($tokenList)) {
        $this->has_token = TRUE;
      }
    }
    return $this->has_token;
  }


  private function encrypt_file($sourceFile, $targetFile)
  {
    if (!file_exists($sourceFile) || filesize($sourceFile) === 0) {
      Corelog::log("Source file missing or empty: $sourceFile", Corelog::ERROR);
      return false;
    }
    $streamed_file = fopen($sourceFile, "r");
    $source_data = fread($streamed_file, filesize($sourceFile));
    fclose($streamed_file);
    $encrypted_data = $this->AESEncode($source_data);
    if (file_put_contents($targetFile, $encrypted_data) === false) {
      Corelog::log("Failed to write encrypted file: $targetFile", Corelog::ERROR);
      return false;
    }
    Corelog::log("Converting uploaded file into Encrypted file", Corelog::CRUD);
    exec("rm -rf '$sourceFile'");
  }

  private function decrypt_file($sourceFile, $targetFile)
  {
    $streamed_file = fopen($sourceFile, "r");
    if ($streamed_file === false) {
      Corelog::log("decrypt_file: cannot open source: $sourceFile", Corelog::ERROR);
      return false;
    }
    $source_data = fread($streamed_file, filesize($sourceFile));
    fclose($streamed_file);
    // Encrypt the file contents
    $decrypted_data = $this->AESDecode($source_data);
    file_put_contents($targetFile, $decrypted_data);
    // Store file 
    exec(\ICT\Core\sys_which('txt2pdf', 'usr/sbin') . " $targetFile");
    Corelog::log("Converting encrypted file into pdf", Corelog::CRUD);
    // Return the decrypted file
    return $targetFile;
  }


  private function AESEncode($input, $user_id = null)
  {
    // Get Key
    if ($user_id) {
      $oUser = new User($user_id);
      $secret = $oUser->secret;
    } else {
      $oSession = Session::get_instance();
      $secret = $oSession->user->secret;
    }
    // Generate IV
    $iv = Random::string(16);
    // Setup Ciper
    $cipher = new AES('ctr');
    $cipher->setIV($iv);
    $cipher->setKey(hash('sha256', md5($secret), true));
    // Encrypt
    return $cipher->encrypt($input) . ":µð!#äctŒ:" . $iv;
  }

  private function AESDecode($input)
  {
    // Separate iv, user_id from data
    $input = explode(":µð!#äctŒ:", $input);
    $encrypted_data = $input[0];
    $iv = $input[1];
    // Get key
    $oSession = Session::get_instance();
    $secret = $oSession->user->secret;
    // Setup Ciper
    $cipher = new AES('ctr');
    $cipher->setIV($iv);

    $cipher->setKey(hash('sha256', md5($secret), true));
    // Descript
    return $cipher->decrypt($encrypted_data);
  }

  public function token_apply(Token $oToken, $default_value = Token::KEEP_ORIGNAL)
  {
    global $path_cache;
    $personalizedFile = array();
    foreach ($this->aSource as $source_file) {
      $source_type = $this->file_type($source_file);
      $source_content = $this->token_read_content($source_file);
      $personalized_content = $oToken->render_variable($source_content, $default_value);
      $personalized_file = tempnam($path_cache, "personalized_document_") . ".$source_type"; // we are using random file name, to avoid, overwriting the existing one
      $this->token_write_content($source_file, $personalized_file, $personalized_content);
      $personalizedFile[] = $personalized_file;
      //$this->token_remove_content($source_file);
    }
    $this->aSource = $personalizedFile;
    $this->token_applied = TRUE;
  }
  public static function create_jpg($sourceFile, $page_no = 1)
  {
    $jpgFile = $sourceFile . '_' . $page_no . '.jpg';

    $images = new Imagick($sourceFile);
    for ($i = 1; $i <= $page_no; $i++) {
      $images->next();
    }
    $thumb = $images->current();

    // Providing 0 forces thumbnailImage to maintain aspect ratio
    $thumb->setResolution(204, 98);
    $thumb->resampleImage(98, 98, imagick::FILTER_UNDEFINED, 1);
    // $thumb->thumbnailImage(300,0); show full size
    $thumb->setImageCompression(imagick::COMPRESSION_JPEG);
    $thumb->setImageCompressionQuality(90);
    $thumb->writeImage($jpgFile);

    return $jpgFile;
  }

  public function get_jpg_file($page_no)
  {
    return $this->create_jpg($page_no);
  }

  public function get_link()
  {
    return $this->file_name;
  }

  private function token_read_content($source_file)
  {
    // extract content.xml and make the changes
    switch ($this->file_type($source_file)) {
      case 'odt':
        $cache_dir = $this->cache_dir($source_file);
        $file_name = $cache_dir . '/content.xml';
        exec("cd $cache_dir && unzip -p \"$source_file\" content.xml > content.xml");
        break;
      case 'docx':
        $cache_dir = $this->cache_dir($source_file);
        $file_name = $cache_dir . '/word/document.xml';
        mkdir($cache_dir . '/word', 0744, true);
        exec("cd $cache_dir && unzip -p \"$source_file\" word/document.xml > word/document.xml");
        break;
      default:
        $file_name = $source_file;
    }

    $regex = "/\[([^\[\]]+)\]/";
    $file_content = file_get_contents($file_name);
    $file_content = preg_replace_callback($regex, array($this, '_token_read_content'), $file_content);
    return $file_content;
  }

  public function _token_read_content($org_matchs)
  {
    $clean_match = strip_tags($org_matchs[0]);
    if (Token::is_token($clean_match)) {
      return $clean_match;
    } else {
      return $org_matchs[0];
    }
  }

  private function token_write_content($source_file, $personalized_file, $personalized_content)
  {
    // update the document archive
    switch ($this->file_type($source_file)) {
      case 'odt':
        $cache_dir = $this->cache_dir($source_file);
        copy($source_file, $personalized_file);
        file_put_contents($cache_dir . '/content.xml', $personalized_content);
        exec("cd $cache_dir && zip \"$personalized_file\" content.xml");
        Corelog::log("cd $cache_dir && zip \"$personalized_file\" content.xml", Corelog::INFO);
        break;
      case 'docx':
        copy($source_file, $personalized_file);
        $cache_dir = $this->cache_dir($source_file);
        file_put_contents($cache_dir . '/word/document.xml', $personalized_content);
        exec("cd $cache_dir && zip \"$personalized_file\" word/document.xml");
        Corelog::log("cd $cache_dir && zip \"$personalized_file\" word/document.xml", Corelog::INFO);
        break;
      default:
        file_put_contents($personalized_file, $personalized_content);
        break;
    }
  }

  private function token_remove_content($source_file)
  {
    $cache_dir = $this->cache_dir($source_file);
    exec("rm -rf $cache_dir");
  }

  private function file_type($source_file, $default_type = 'pdf')
  {
    $file_type = pathinfo($source_file, PATHINFO_EXTENSION);
    if (empty($file_type)) {
      $fileType  = explode('.', $source_file);
      $file_type = end($fileType);
      $file_type = empty($file_type) ? strtolower($default_type) : strtolower($file_type);
    }
    return $file_type;
  }

  private function cache_dir($source_file)
  {
    global $path_cache, $path_data;
    $data_regex = '/^' . addcslashes($path_data, '/') . '/'; // regular expression against 
    $cache_dir  = preg_replace($data_regex, $path_cache, $source_file);
    mkdir($cache_dir, 0744, true);
    return $cache_dir;
  }
}
