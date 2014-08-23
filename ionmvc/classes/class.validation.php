<?php

namespace ionmvc\classes;

use ionmvc\classes\config\string as config_string;
use ionmvc\exceptions\app as app_exception;
use ionmvc\exceptions\validation_exception;

class validation {

	private $fields = array();
	private $groups = array();
	private $errors = array();
	private $messages = array(
		'required'            => '%s is required',
		'exact_length'        => '%s must be exactly %d characters long',
		'min_length'          => '%s must be longer than or equal to %d characters',
		'max_length'          => '%s must be shorter than or equal to %d characters',
		'numeric'             => '%s must be numeric',
		'in_multi'            => '%s contains a invalid value',
		'in'                  => '%s is not a valid value',
		'matches'             => '%s does not match \'%s\'',
		'not_match'           => '%s should not match \'%s\'',
		'compare'             => '%s does not match %s',
		'date_part'           => '%s is invalid',
		'date_format_1'       => '%s must be in the format %s',
		'date_format_2'       => '%s is not a valid date',
		'date_before'         => '%s must be before %s',
		'date_after'          => '%s must be after %s',
		'email'               => '%s is invalid',
		'password'            => '%s is not valid - reason: %s',
		'password_reason_1'   => 'must be at least %s characters long',
		'password_reason_2'   => 'uppercase letter',
		'password_reason_3'   => 'lowercase letter',
		'password_reason_4'   => 'number',
		'password_reason_5'   => 'special character',
		'url'                 => '%s is not a valid url',
		'phone'               => '%s must be in the format: %s',
		'regex'               => '%s is not valid',
		'decimal_1'           => '%s must contain a decimal point',
		'decimal_2'           => '%s must be in the format: %s.%s',
		'upload_choose_file'  => '%s - Please choose a file to upload',
		'upload_invalid_file' => '%s - File is not valid',
		'upload_invalid_extn' => '%s - File extension not allowed. Only %s allowed.',
		'upload_invalid_img'  => '%s - File is not a valid image',
		'upload_invalid_size' => '%s - File exceeds the maximum file size of %s',
		'upload_image_small'        => '%s - Image dimensions must be greater than %spx x %spx',
		'upload_image_small_width'  => '%s - Image width must be greater than %spx',
		'upload_image_small_height' => '%s - Image height must be greater than %spx',
		'upload_image_large'        => '%s - Image dimensions must be less than %spx x %spx',
		'upload_image_large_width'  => '%s - Image width must be less than %spx',
		'upload_image_large_height' => '%s - Image height must be less than %spx',
		'group_required'      => 'At least %d %s is required'
	);
	private $passed = false;

	public function __construct( $input,$rules,$labels=array() ) {
		foreach( $rules as $field => $_rules ) {
			$value = array_func::get( $input,$field,false );
			if ( $value !== false ) {
				$value = trim( $value );
				if ( strlen( $value ) === 0 ) {
					$value = false;
				}
			}
			$this->fields[$field] = array(
				'label' => ( isset( $labels[$field] ) ? $labels[$field] : $field ),
				'rules' => config_string::parse( $_rules ),
				'value' => $value,
				'valid' => false
			);
		}
	}

	public function group( $name,$fields,$config='' ) {
		if ( !isset( $this->groups[$name] ) ) {
			$this->groups[$name] = array(
				'fields' => array(),
				'config' => config_string::parse( $config )
			);
		}
		foreach( (array) $fields as $field ) {
			if ( isset( $this->fields[$field] ) ) {
				$this->fields[$field]['group'] = $name;
			}
			$this->groups[$name]['fields'][] = $field;
		}
		return $this;
	}

	public function validate() {
		//run the validation
		//get last fields of groups for required fields in group stuff
		foreach( $this->fields as $field => $config ) {
			if ( !isset( $config['rules']['required'] ) && $config['value'] === false ) {
				continue;
			}
			$rules = array_keys( $config['rules'] );
			while( count( $rules ) > 0 ) {
				$rule = array_shift( $rules );
				$label = $config['label'];
				if ( isset( $config['rules'][$rule] ) ) {
					$param = $config['rules'][$rule];
				}
				try {
					switch( $rule ) {
						case 'required':
							if ( !isset( $config['rules']['upload'] ) && $config['value'] === false ) {
								throw new validation_exception( $this->messages[$rule],$label );
							}
							if ( isset( $config['rules']['upload'] ) && ( $config['value'] === false || $config['value']['error'] !== UPLOAD_ERR_OK || !input::has_file( $field ) ) ) {
								throw new validation_exception( $this->messages['upload_choose_file'],$label );
							}
						break;
						case 'required_if':
							if ( !is_array( $param ) ) {
								throw new app_exception('Please provide a type for the required_if rule');
							}
							if ( isset( $param['field_equals'] ) ) {
								$fields = array_chunk( $param['field_equals'],2 );
								foreach( $fields as $_field ) {
									if ( count( $_field ) !== 2 ) {
										throw new app_exception( 'Rule %s requires parameters grouped in 2\'s',"{$rule}:field_equals" );
									}
									list( $__field,$value ) = $_field;
									if ( $this->fields[$__field]['value'] === false ) {
										continue;
									}
									if ( ( is_array( $this->fields[$__field]['value'] ) && in_array( $value,$this->fields[$__field]['value'] ) ) || ( !is_array( $this->fields[$__field]['value'] ) && $this->fields[$__field]['value'] == $value ) ) {
										array_unshift( $rules,'required' );
										break;
									}
								}
							}
							if ( isset( $param['has_value'] ) ) {
								foreach( explode( ',',$param['has_value'] ) as $_field ) {
									if ( $field == $_field ) {
										continue;
									}
									if ( $this->fields[$_field]['value'] !== false ) {
										array_unshift( $rules,'required' );
										break;
									}
								}
							}
						break;
						case 'group_required':
						
						break;
						case 'exact_length':
							if ( strlen( $config['value'] ) !== (int) $param ) {
								throw new validation_exception( $this->messages[$rule],$label,$param );
							}
						break;
						case 'min_length':
							if ( strlen( $config['value'] ) < $param ) {
								throw new validation_exception( $this->messages[$rule],$label,$param );
							}
						break;
						case 'max_length':
							if ( strlen( $config['value'] ) > $param ) {
								throw new validation_exception( $this->messages[$rule],$label,$param );
							}
						break;
						case 'numeric':
							if ( !is_numeric( $config['value'] ) ) {
								throw new validation_exception( $this->messages[$rule],$label );
							}
						break;
						case 'in':
						case 'not_in':
							$array = array();
							if ( is_array( $config['value'] ) ) {
								foreach( $config['value'] as $value ) {
									$result = in_array( $value,$array );
									if ( ( $rule == 'in' && $result === false ) || ( $rule == 'not_in' && $result === true ) ) {
										throw new validation_exception( $this->messages["{$rule}_multi"],$label );
									}
								}
								break;
							}
							$result = in_array( $config['value'],$array );
							if ( ( $rule == 'in' && $result === false ) || ( $rule == 'not_in' && $result === true ) ) {
								throw new validation_exception( $this->messages[$rule],$label );
							}
						break;
						case 'same':
						case 'different':
							if ( $this->fields[$param]['value'] === false || ( $rule == 'same' && $config['value'] !== $this->fields[$param]['value'] ) || ( $rule == 'different' && $config['value'] == $this->fields[$param]['value'] ) ) {
								throw new validation_exception( $this->messages[$rule],$label );
							}
						break;
						case 'date':
							if ( !is_array( $param ) ) {
								throw new app_exception('Please provide a type for the date rule');
							}
							foreach( $param as $type => $datum ) {
								switch( $type ) {
									case 'part':
										$error = false;
										if ( !is_numeric( $config['value'] ) ) {
											$error = true;
										}
										else {
											$value = (int) $config['value'];
											switch( $datum ) {
												case 'month':
													if ( $value < 1 || $value > 12 ) {
														$error = true;
													}
												break;
												case 'day':
													if ( $value < 1 || $value > 31 ) {
														$error = true;
													}
												break;
												case 'year':
													if ( $value < 1000 || $value > 3000 ) {
														$error = true;
													}
												break;
												default:
													throw new app_exception('Invalid date part');
												break;
											}
										}
										if ( $error == true ) {
											throw new validation_exception( $this->messages['date_part'],$label );
										}
									break;
									case 'format':
										$error = false;
										$format = $datum;
										if ( $format === false ) {
											$format = 'MM-DD-YYYY';
										}
										else {
											$format = strtoupper( $format );
										}
										$sep = substr( str_replace( array( 'MM','DD','YYYY','YY' ),'',$format ),0,1 );
										$format_parts = explode( $sep,$format );
										$regexes = array(
											'MM'   => '([0-9]{2})',
											'DD'   => '([0-9]{2})',
											'YY'   => '([0-9]{2})',
											'YYYY' => '([0-9]{4})'
										);
										$regex = array();
										foreach( $format_parts as $part ) {
											$regex[] = $regexes[$part];
										}
										if ( preg_match( '#^' . implode( '[ /\-\.]{1}',$regex ) . '$#',$field_value,$matches ) !== 1 ) {
											throw new validation_exception( $this->messages['date_format_1'],$label,$format );
										}
										$i = 1;
										foreach( $format_parts as $part ) {
											$value = (int) $matches[$i];
											switch( $part ) {
												case 'MM':
													if ( $value < 1 || $value > 12 ) {
														$error = true;
													}
												break;
												case 'DD':
													if ( $value < 1 || $value > 31 ) {
														$error = true;
													}
												break;
												case 'YY':
												case 'YYYY':
													if ( strlen( $matches[$i] ) == 3 || ( $part == 'YYYY' && ( $value < 1000 || $value > 3000 ) ) ) {
														$error = true;
													}
												break;
											}
											$i++;
										}
										if ( $error == true ) {
											throw new validation_exception( $this->messages['date_format_2'],$label );
										}
									break;
									case 'before':
									case 'before_field':
									case 'after':
									case 'after_field':
										if ( func::str_at_end( 'field',$type ) ) {
											$field_format = 'MM-DD-YYYY';
											if ( isset( $data['field_format'] ) ) {
												$field_format = $param['field_format'];
											}
											if ( $this->fields[$datum]['value'] === false ) {
												break;
											}
											$time = strtotime( time::convert_date( $field_format,'YYYY-MM-DD',$config['value'] ) );
										}
										else {
											$time = $datum;
											if ( $time == 'now' ) {
												$time = time::now();
											}
											elseif ( is_numeric( $time ) ) {
												$time = (int) $time;
											}
											elseif ( is_string( $time ) ) {
												$time = strtotime( $time );
											}
										}
										$format = ( isset( $param['format'] ) ? $param['format'] : 'MM-DD-YYYY' );
										if ( ( $type == 'before' || $type == 'before_field' ) && $value > $time ) {
											throw new validation_exception( $this->messages['date_before'],$label,date( 'm-d-Y',$time ) );
										}
										if ( ( $type == 'after' || $type == 'after_field' ) && $value < $time ) {
											throw new validation_exception( $this->messages['date_after'],$label,date( 'm-d-Y',$time ) );
										}
									break;
								}
							}
						break;
						case 'email':
							if ( !func::validate_email( $config['value'] ) ) {
								throw new validation_exception( $this->messages[$rule],$label );
							}
						break;
						case 'password':
							$messages = array();
							if ( strlen( $config['value'] ) < $param ) {
								$messages['length'] = sprintf( $this->messages['password_reason_1'],$param );
							}
							if ( preg_match( '/[A-Z]/',$config['value'] ) === 0 ) {
								$messages['uppercase'] = $this->messages['password_reason_2'];
							}
							if ( preg_match( '/[a-z]/',$config['value'] ) === 0 ) {
								$messages['lowercase'] = $this->messages['password_reason_3'];
							}
							if ( preg_match( '/[0-9]/',$config['value'] ) === 0 ) {
								$messages['number'] = $this->messages['password_reason_4'];
							}
							if ( preg_match( '/[^a-zA-Z0-9]/',$config['value'] ) === 0 ) {
								$messages['special'] = $this->messages['password_reason_5'];
							}
							$errstr = '';
							if ( isset( $messages['length'] ) ) {
								$errstr .= sprintf( $this->errmsg['password_reason_1'],$param );
							}
							if ( isset( $messages['uppercase'] ) || isset( $messages['lowercase'] ) || isset( $messages['number'] ) || isset( $messages['special'] ) ) {
								$errstr .= ( isset( $messages['length'] ) ? ' and ' : ' must ' ) . 'have at least one ';
							}
							if ( isset( $messages['length'] ) ) {
								unset( $messages['length'] );
							}
							$errstr .= implode( ', ',$messages );
							if ( $errstr !== '' ) {
								throw new validation_exception( $this->messages[$rule],$label,$errstr );
							}
						break;
						case 'url':
							if ( strpos( $config['value'],'http://' ) === false && strpos( $config['value'],'https://' ) === false ) {
								throw new validation_exception( $this->messages[$rule],$label );
							}
						break;
						case 'phone':
							preg_match_all( '#[X]+#',$param,$matches );
							if ( !isset( $matches[0] ) || count( $matches[0] ) == 0 ) {
								throw new app_exception('Invalid phone number format');
							}
							$parts = preg_split( '#[X]+#',$param );
							if ( !isset( $parts[0] ) ) {
								throw new app_exception('Invalid phone number format');
							}
							$format = array();
							foreach( $parts as &$part ) {
								$part = str_replace( array('[','\\','^','$','.','|','?','*','+','(',')','{','}'),array('\[','\\\\','\^','\$','\.','\|','\?','\*','\+','\(','\)','\{','\}'),$part );
							}
							foreach( $matches[0] as &$match ) {
								$match = '[0-9]{' . strlen( $match ) . '}';
							}
							$array_one = $parts;
							$array_two = $matches[0];
							for( $i=0,$e_i=0,$o_i=0;$i < ( count( $array_one ) + count( $array_two ) );$i++ ) {
								if ( $i % 2 == 0 ) {
									if ( !isset( $array_one[$e_i] ) ) { //first
										continue;
									}
									$format[] = $array_one[$e_i];
									$e_i++;
								}
								else {
									if ( !isset( $array_two[$o_i] ) ) { //second
										continue;
									}
									$format[] = $array_two[$o_i];
									$o_i++;
								}
							}
							$format = implode( '',array_filter( $format ) );
							if ( preg_match( "#^{$format}$#",$config['value'] ) !== 1 ) {
								throw new validation_exception( $this->messages[$rule],$label,$param );
							}
						break;
						case 'decimal':
							list( $before,$after ) = $param;
							if ( strpos( $config['value'],'.' ) === false ) {
								throw new validation_exception( $this->messages['decimal_1'],$label );
							}
							list( $b,$a ) = explode( '.',$config['value'],2 );
							if ( preg_match( '#^[0-9]+$#',$b ) !== 1 || preg_match( '#^[0-9]+$#',$a ) !== 1 || strlen( $b ) > $before || strlen( $a ) > $after ) {
								throw new validation_exception( $this->messages['decimal_2'],$label,str_repeat( 'X',$before ),str_repeat( 'X',$after ) );
							}
						break;
						case 'regex':
							switch( $param ) {
								case 'alphanum':
									$regex = '^[a-zA-Z0-9]+$';
								break;
								case 'alphanum_dash':
									$regex = '^[a-zA-Z0-9_\-]+$';
								break;
								case 'alpha':
									$regex = '^[a-zA-Z]+$';
								break;
								case 'datetime':
									$regex = '^(1[012]{1}|0[1-9]{1})[/\-\. ]{1}(0[1-9]{1}|[12]{1}[0-9]{1}|3[01]{1})[/\-\. ]{1}((19|20)[0-9]{2})[\s]+(0[1-9]{1}|1[012]{1})[\:]{1}([012345]{1}[0-9]{1})[\:]{1}([012345]{1}[0-9]{1})[\s]+([aApP]{1}[mM]{1})$';
								break;
								default:
									throw new app_exception('Invalid regex name');
								break;
							}
							if ( preg_match( "#{$regex}#",$field_value,$matches ) !== 1 ) {
								throw new validation_exception( $this->messages[$rule],$label );
							}
						break;
						case 'upload':
							$upload_config = $rules['upload'];
							if ( count( ( $diff = array_diff( array('exts','maxsize','directory'),array_keys( $upload_config ) ) ) ) > 0 ) {
								throw new app_exception( 'Missing config vars: %s',implode( ', ',$diff ) );
							}
							if ( ( $maxsize = file::to_bytes( $upload_config['maxsize'] ) ) === false ) {
								throw new app_exception('Invalid max file size');
							}
							$upload_config['maxsize'] = $maxsize;
							if ( !is_array( $upload_config['exts'] ) ) {
								$upload_config['exts'] = explode( ',',$upload_config['exts'] );
							}
							if ( !is_uploaded_file( $config['value']['tmp_name'] ) ) {
								throw new validation_exception( $this->messages['upload_invalid_file'],$label );
							}
							$extn = file::get_extension( $config['value']['tmp_name'] );
							if ( !in_array( $extn,$upload_config['exts'] ) ) {
								throw new validation_exception( $this->messages['upload_invalid_extn'],$label,implode( ', ',$upload_config['exts'] ) );
							}
							$image = false;
							if ( in_array( $extn,array('jpg','jpeg','gif','png','tiff','bmp') ) ) {
								$image = true;
							}
							if ( $image === true && false === ( $info = getimagesize( $config['value']['tmp_name'] ) ) ) {
								throw new validation_exception( $this->messages['upload_invalid_img'],$label );
							}
							if ( filesize( $config['value']['tmp_name'] ) > $upload_config['maxsize'] ) {
								throw new validation_exception( $this->messages['upload_invalid_size'],$label,file::format_filesize( $upload_config['maxsize'] ) );
							}
							if ( $image === true && isset( $info ) ) {
								list( $width,$height ) = $info;
								//checking minimum dimensions
								if ( isset( $upload_config['min_dimensions'] ) ) {
									if ( count( $upload_config['min_dimensions'] ) !== 2 ) {
										throw new app_exception('Rule \'upload:min_dimensions\' requires two parameters: [width][height]');
									}
									list( $upload_config['min_width'],$upload_config['min_height'] ) = $upload_config['min_dimensions'];
								}
								$min_width = $min_height = false;
								if ( isset( $upload_config['min_width'] ) && $width < $upload_config['min_width'] ) {
									$min_width = true;
								}
								if ( isset( $upload_config['min_height'] ) && $height < $upload_config['min_height'] ) {
									$min_height = true;
								}
								if ( $min_width == true && $min_height == true ) {
									throw new validation_exception( $this->messages['upload_image_small'],$label,$upload_config['min_width'],$upload_config['min_height'] );
								}
								if ( $min_width == true ) {
									throw new validation_exception( $this->messages['upload_image_small_width'],$label,$upload_config['min_width'] );
								}
								if ( $min_height == true ) {
									throw new validation_exception( $this->messages['upload_image_small_height'],$label,$upload_config['min_height'] );
								}
								//checking maximum dimensions
								if ( isset( $upload_config['max_dimensions'] ) ) {
									if ( count( $upload_config['max_dimensions'] ) !== 2 ) {
										throw new app_exception('Rule \'upload:max_dimensions\' requires two parameters: [width][height]');
									}
									list( $upload_config['max_width'],$upload_config['max_height'] ) = $upload_config['max_dimensions'];
								}
								$max_width = $max_height = false;
								if ( isset( $upload_config['max_width'] ) && $width > $upload_config['max_width'] ) {
									$max_width = true;
								}
								if ( isset( $upload_config['max_height'] ) && $height > $upload_config['max_height'] ) {
									$max_height = true;
								}
								if ( $max_width == true && $max_height == true ) {
									throw new validation_exception( $this->messages['upload_image_large'],$label,$upload_config['max_width'],$upload_config['max_height'] );
								}
								if ( $max_width == true ) {
									throw new validation_exception( $this->messages['upload_image_large_width'],$label,$upload_config['max_width'] );
								}
								if ( $max_height == true ) {
									throw new validation_exception( $this->messages['upload_image_large_height'],$label,$upload_config['max_height'] );
								}
							}
						break;
						default:
							if ( in_array( $param,$funcs['user'] ) ) {
								$retval = $param( $config['value'] );
								if ( $retval !== true ) {
									throw new validation_exception( $retval,$label );
								}
								break;
							}
							throw new app_exception('Invalid rule');
						break;
					}
				}
				catch( validation_exception $e ) {
					$this->errors[$field] = $e->getMessage();
					continue 2;
				}
			}
		}
		if ( count( $this->errors ) === 0 ) {
			$this->passed = true;
		}
	}

	public function failed() {
		return !$this->passed;
	}

	public function passed() {
		return $this->passed;
	}

	public function errors() {
		return $this->errors;
	}

	public function error( $field ) {
		if ( isset( $this->errors[$field] ) ) {
			return $this->errors[$field];
		}
		return false;
	}

	public static function make( $input,$rules,$labels=array() ) {
		return new self( $input,$rules,$labels );
	}

	public static function run( $input,$rules,$labels=array() ) {
		$obj = new self( $input,$rules,$labels );
		$obj->validate();
		return $obj;
	}

}

?>