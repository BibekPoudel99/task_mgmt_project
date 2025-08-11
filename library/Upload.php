<?php
class Upload
{
	private static $_upload_path = null;
	private static $_upload_size = null;
	private static $_upload_ext = array();
	private static $_allowed_mimes = array();
	private static $_upload_error = [
		1 => 'File is larger than upload_max_filesize',
		2 => 'File is larger than MAX_FILE_SIZE',
		3 => 'File was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Missing temporary directory',
		7 => 'Failed to write file to disk',
		8 => 'File upload stopped by extension' 
	];
	private static $_errors = [];	

	public static function initialize($configs = array()){
		if(empty($configs)) throw new Exception("Please provide necessary configurations.");
		
		// Set allowed extensions
		self::$_upload_ext = explode('|', strtolower($configs['upload_ext']));
		
		// Set upload path and create directory if it doesn't exist
		self::$_upload_path = rtrim($configs['upload_path'], '/') . '/';
		if (!is_dir(self::$_upload_path)) {
			if (!mkdir(self::$_upload_path, 0755, true)) {
				throw new Exception("Could not create upload directory: " . self::$_upload_path);
			}
		}
		
		// Set max upload size
		self::$_upload_size = $configs['upload_size'];
		
		// Set allowed MIME types for security
		self::$_allowed_mimes = isset($configs['allowed_mimes']) ? 
			$configs['allowed_mimes'] : self::getDefaultMimes();
	}

	public static function load($file = array()){
		if (empty($file)) throw new Exception("File details not found");
		
		// Reset errors
		self::$_errors = [];
		
		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$original_name = $file['name'];
		$tmp = $file['tmp_name'];
		$size = $file['size'];
		$error = (int)$file['error'];

		// Check for upload errors
		if ($error !== 0){
			self::$_errors[] = self::$_upload_error[$error];
			return false;
		}

		// Check file size
		if ($size > self::$_upload_size){
			self::$_errors[] = 'Maximum upload size is ' . (self::$_upload_size / 1048576) . ' MB';
			return false;
		}

		// Check if file is empty
		if ($size == 0) {
			self::$_errors[] = 'File is empty';
			return false;
		}

		// Check file extension
		if(!in_array($ext, self::$_upload_ext)){
			self::$_errors[] = 'Extension not allowed. Allowed file types: ' . implode(', ', self::$_upload_ext);
			return false;
		}

		// Validate MIME type for security (prevents file type spoofing)
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $tmp);
			finfo_close($finfo);
			
			if (!in_array($mime, self::$_allowed_mimes)) {
				self::$_errors[] = 'File type not allowed. Detected type: ' . $mime;
				return false;
			}
		}

		// Additional security checks
		if (!is_uploaded_file($tmp)) {
			self::$_errors[] = 'File was not uploaded via HTTP POST';
			return false;
		}

		// Generate secure filename
		$filename = self::generateSecureFilename($original_name, $ext);
		$full_path = self::$_upload_path . $filename;

		// Move uploaded file
		if(!move_uploaded_file($tmp, $full_path)) {
			self::$_errors[] = "Failed to move uploaded file";
			return false;
		}

		// Set proper file permissions
		chmod($full_path, 0644);

		return $filename;
	}

	public static function getErrors(){
		return is_array(self::$_errors) ? self::$_errors : [self::$_errors];
	}

	public static function hasErrors(){
		return !empty(self::$_errors);
	}

	public static function getUploadPath(){
		return self::$_upload_path;
	}

	public static function deleteFile($filename){
		if (empty($filename)) return false;
		
		$file_path = self::$_upload_path . $filename;
		if (file_exists($file_path)) {
			return unlink($file_path);
		}
		return false;
	}

	public static function getFileSize($filename){
		if (empty($filename)) return false;
		
		$file_path = self::$_upload_path . $filename;
		if (file_exists($file_path)) {
			return filesize($file_path);
		}
		return false;
	}

	public static function getReadableFileSize($bytes) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		return round($bytes, 2) . ' ' . $units[$pow];
	}

	private static function generateSecureFilename($original_name, $ext){
		// Remove extension from original name
		$name_without_ext = pathinfo($original_name, PATHINFO_FILENAME);
		
		// Sanitize filename
		$safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name_without_ext);
		$safe_name = substr($safe_name, 0, 50); // Limit length
		
		// Generate unique hash
		$hash = md5(time() . rand() . $original_name);
		
		// Combine for unique but recognizable filename
		return $safe_name . '_' . substr($hash, 0, 8) . '.' . $ext;
	}

	private static function getDefaultMimes(){
		return [
			// Images
			'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
			// Documents
			'application/pdf',
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			// Text
			'text/plain', 'text/csv',
			// Archives
			'application/zip', 'application/x-zip-compressed'
		];
	}

	// Multiple file upload support
	public static function loadMultiple($files = array()){
		if (empty($files)) return false;
		
		$uploaded_files = [];
		$file_count = count($files['name']);
		
		for ($i = 0; $i < $file_count; $i++) {
			$file = [
				'name' => $files['name'][$i],
				'type' => $files['type'][$i],
				'tmp_name' => $files['tmp_name'][$i],
				'error' => $files['error'][$i],
				'size' => $files['size'][$i]
			];
			
			$filename = self::load($file);
			if ($filename) {
				$uploaded_files[] = $filename;
			}
		}
		
		return $uploaded_files;
	}
}