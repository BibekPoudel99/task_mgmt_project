<?php
class User extends Model
{
	protected $table = "users";
	protected $key = "id";
	protected $field = "*";

	public function __construct()
	{	
		parent::__construct();
	}

	// Create new user with validation
	public function createUser($data, $profile_image = null)
	{
		// Validate input data
		$validation_errors = $this->validateUserData($data);
		if (!empty($validation_errors)) {
			return ['success' => false, 'errors' => $validation_errors];
		}

		// Hash password
		$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
		
		// Remove confirm password (not needed in database)
		unset($data['cpassword']);

		// Handle profile image upload if provided
		if ($profile_image && !empty($profile_image['tmp_name'])) {
			$upload_result = $this->handleProfileImageUpload($profile_image);
			
			if ($upload_result['success']) {
				$data['profile_image'] = $upload_result['filename'];
			} else {
				return ['success' => false, 'errors' => $upload_result['errors']];
			}
		}

		// Add timestamps
		$data['created_at'] = date('Y-m-d H:i:s');
		$data['updated_at'] = date('Y-m-d H:i:s');

		// Save user to database
		try {
			$user_id = $this->save($data);
			if ($user_id) {
				return [
					'success' => true, 
					'user_id' => $user_id,
					'message' => 'User created successfully'
				];
			} else {
				return ['success' => false, 'errors' => ['Failed to create user']];
			}
		} catch (Exception $e) {
			return ['success' => false, 'errors' => [$e->getMessage()]];
		}
	}

	// Validate user input data
	private function validateUserData($data)
	{
		$errors = [];

		// Username validation
		if (empty($data['username'])) {
			$errors[] = 'Username is required';
		} elseif (strlen($data['username']) < 3) {
			$errors[] = 'Username must be at least 3 characters';
		} elseif (strlen($data['username']) > 20) {
			$errors[] = 'Username must not exceed 20 characters';
		} elseif ($this->usernameExists($data['username'])) {
			$errors[] = 'Username already exists';
		}

		// Email validation
		if (empty($data['email'])) {
			$errors[] = 'Email is required';
		} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors[] = 'Please enter a valid email address';
		} elseif ($this->emailExists($data['email'])) {
			$errors[] = 'Email already exists';
		}

		// Password validation
		if (empty($data['password'])) {
			$errors[] = 'Password is required';
		} elseif (strlen($data['password']) < 6) {
			$errors[] = 'Password must be at least 6 characters';
		}

		// Confirm password validation
		if (empty($data['cpassword'])) {
			$errors[] = 'Please confirm your password';
		} elseif ($data['password'] !== $data['cpassword']) {
			$errors[] = 'Passwords do not match';
		}

		// User type validation
		if (empty($data['usertype'])) {
			$errors[] = 'User type is required';
		}

		return $errors;
	}

	// Handle profile image upload
	private function handleProfileImageUpload($file)
	{
		try {
			// Initialize upload settings
			Upload::initialize([
				'upload_size' => 2000000, // 2MB
				'upload_ext' => 'jpg|jpeg|png|gif|webp',
				'upload_path' => 'uploads/users/profiles',
				'allowed_mimes' => [
					'image/jpeg', 'image/jpg', 'image/png', 
					'image/gif', 'image/webp'
				]
			]);

			$filename = Upload::load($file);
			
			if (Upload::hasErrors()) {
				return ['success' => false, 'errors' => Upload::getErrors()];
			}

			return ['success' => true, 'filename' => $filename];
			
		} catch (Exception $e) {
			return ['success' => false, 'errors' => [$e->getMessage()]];
		}
	}

	// Check if username exists
	public function usernameExists($username)
	{
		$result = $this->getBy('username = ?', [$username], true);
		return $result !== false;
	}

	// Check if email exists
	public function emailExists($email)
	{
		$result = $this->getBy('email = ?', [$email], true);
		return $result !== false;
	}

	// Find user by email (for login)
	public function findByEmail($email)
	{
		return $this->getBy('email = ?', [$email], true);
	}

	// Find user by username (for login)
	public function findByUsername($username)
	{
		return $this->getBy('username = ?', [$username], true);
	}

	// Verify user password
	public function verifyPassword($password, $hash)
	{
		return password_verify($password, $hash);
	}

	// Update user profile
	public function updateProfile($user_id, $data)
	{
		// Remove sensitive fields that shouldn't be updated this way
		unset($data['password'], $data['id'], $data['created_at']);
		
		$data['updated_at'] = date('Y-m-d H:i:s');
		
		return $this->save($data, $user_id);
	}

	// Change user password
	public function changePassword($user_id, $old_password, $new_password)
	{
		$user = $this->get($user_id);
		
		if (!$user || !$this->verifyPassword($old_password, $user->password)) {
			return ['success' => false, 'error' => 'Current password is incorrect'];
		}

		$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
		$updated = $this->save(['password' => $hashed_password], $user_id);

		if ($updated) {
			return ['success' => true, 'message' => 'Password changed successfully'];
		} else {
			return ['success' => false, 'error' => 'Failed to update password'];
		}
	}

	// Get user with profile info (excluding password)
	public function getProfile($user_id)
	{
		$user = $this->get($user_id);
		if ($user) {
			unset($user->password); // Never return password
			return $user;
		}
		return false;
	}

	// Delete user and their profile image
	public function deleteUser($user_id)
	{
		$user = $this->get($user_id);
		
		if ($user && !empty($user->profile_image)) {
			// Delete profile image file
			Upload::deleteFile($user->profile_image);
		}
		
		return $this->delete($user_id);
	}
}