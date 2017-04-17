<?php
/**
 * Plugin Name:     Kktt Free Room Announce
 * Plugin URI:
 * Description:     Let you know when the room under contract becomes vacant room.
 * Author:          Hibou
 * Author URI:      https://private.hibou-web.com
 * Text Domain:     kktt-free-room-announce
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Kktt_Free_Room_Announce
 */

if ( class_exists( 'Kktt_Free_Room_Announce' ) ) {
	$kktt_free_room_announce = new Kktt_Free_Room_Announce();
}

/**
 * Summary.
 *
 * @since  0.1.0
 * @access public
 */
class Kktt_Free_Room_Announce {

    public $repository_num = 4;
    public $max_column = 36;
    public $stop_mail_address = '';
    public $delete_flag = false;
    public $delete_address = '';
    public $send_flag = '';

    protected $options;

	/**
	 * Post_Notifier constructor.
	 */
	function __construct() {

	    $this->options = get_option( 'kktt_free_room_announce_settings' );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'kktt_free_room_announce_admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'kktt_free_room_announce_front_end_enqueue_scripts' ), 999 );
		add_shortcode( 'terminal_table', array( $this, 'add_terminal_table' ) );
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		/**
		 * Compare old and new option data.
         * If that is not same, then set send email flag to true. :)
		 */
		//add_filter( 'pre_update_option_kktt_free_room_announce_settings', array( $this, 'check_room_info_options' ), 10, 2 );

	}

	/**
	 * Activation.
	 */
	public function activate() {

		$kktt_free_room_settings = $this->options;
		if ( empty( $kktt_free_room_settings ) ) {

			$default_value = array(
				'kktt_free_room_table_set' => array(),
				'sender_email_field'  => '',
			);
			update_option( 'kktt_free_room_announce_settings', $default_value );

		}

	}

	public function kktt_free_room_announce_admin_enqueue_scripts( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, 'kktt_free_room_announce' ) )
			return;

		wp_enqueue_style(
			'kktt_free_room_announce-admin',
			plugin_dir_url( __FILE__ ) . 'css/style.css',
			array(),
			'',
			'all'
		);

	}

	public function kktt_free_room_announce_front_end_enqueue_scripts() {

	    if ( is_page( 'availability-list' ) ) {

		    wp_enqueue_style(
			    'kktt_free_room_announce-admin',
			    plugin_dir_url( __FILE__ ) . 'css/style-front.css',
			    array(),
			    '',
			    'all'
		    );

		    wp_enqueue_script(
			    'select_terminal-js',
			    plugin_dir_url( __FILE__ ) . '/js/select_terminal.js',
			    array( 'jquery' ),
			    '',
			    true
		    );

	    }

    }

	/**
	 * Add admin menu
	 */
	public function admin_menu() {
		add_options_page(
			'空き状況一覧',
			'空き状況一覧',
			'manage_options',
			'kktt_free_room_announce',
			array( $this, 'kktt_free_room_announce_options_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function settings_init() {

		register_setting(
			'kkttfreeannouncepage',
			'kktt_free_room_announce_settings',
			array( $this, 'data_sanitize' )
		);

		add_settings_field(
			'kktt_sender_email_field',
			__( '送信元メールアドレス', 'kktt-free-room-announce' ),
			array( $this, 'kktt_from_email_render' ),
			'kkttfreeannouncepage',
			'kktt_free_room_announce_kkttfreeannouncepage_section'
		);

		add_settings_section(
			'kktt_free_room_announce_kkttfreeannouncepage_section',
			__( '空き状況一覧', 'kktt-free-room-announce' ),
			array( $this, 'kktt_free_room_announce_settings_section_callback' ),
			'kkttfreeannouncepage'
		);

		add_settings_field(
			'kktt_free_room_table_set',
			__( '空き状況を設定', 'kktt-free-room-announce' ),
			array( $this, 'kktt_free_room_table_set_render' ),
			'kkttfreeannouncepage',
			'kktt_free_room_announce_kkttfreeannouncepage_section'
		);

		add_settings_field(
			'kktt_cell_relation_email',
			__( '各セルに紐付くメールアドレス', 'kktt-free-room-announce' ),
			array( $this, 'kktt_cell_relation_email_render' ),
			'kkttfreeannouncepage',
			'kktt_free_room_announce_kkttfreeannouncepage_section'
		);

	}

	/**
	 * Add description of Post Notifier.
	 */
	public function kktt_free_room_announce_settings_section_callback() {

		echo esc_attr__( '行が一つのコンポーネントになります。ステータスを、"契約中", "商談中", "空き"の3つから設定します。', 'kktt-free-room-announce' );

	}

	public function check_room_info_options( $new_value, $old_value ) {

	    // Check test mode
		if ( $new_value !== $old_value && ! empty( $new_value ) ) {

            //var_dump( $old_value );

			foreach ( $old_value['kktt_free_room_table_set'] as $key => $old_data ) {

			    if ( $old_data === '' ) {

			        $this->send_flag = false;

                } else {

				    $this->send_flag = true;

                }

			}

			//update_option( 'some_option_changed', $new_value );
		}

		return $new_value;
	}


	/**
	 * Output text field.
	 */
	public function kktt_free_room_table_set_render() {

		$html  = '';
		$html .= '<div class="repository_outer">' . "\n";

		for ( $i = 0; $i < $this->repository_num; $i++ ) {

			$html .= '<div class="each_repository">' . "\n";
			$html .= '<table>' . "\n";
			$html .= '<tbody>' . "\n";

			for ( $j = 0; $j < $this->max_column; $j ++ ) {

				switch ( $i ) {

					case 0 :
						$colunm_name_prefix = 'a-';
						break;

					case 1 :
						$colunm_name_prefix = 'b-';
						break;

					case 2 :
						$colunm_name_prefix = 'c-';
						break;

					case 3 :
						$colunm_name_prefix = 'd-';
						break;

				}

				$room_info = isset( $this->options['kktt_free_room_table_set'][$colunm_name_prefix . sprintf( '%02d', ( $j + 1 ) )] )
                    ? $this->options['kktt_free_room_table_set'][$colunm_name_prefix . sprintf( '%02d', ( $j + 1 ) )]
                    : '';

				$company_name = isset( $this->options['kktt_free_room_table_set'][$colunm_name_prefix . ( $j + 1 ) . '_company_name'] )
					? $this->options['kktt_free_room_table_set'][$colunm_name_prefix . ( $j + 1 ) . '_company_name']
					: '';

				$room_type = isset( $this->options['kktt_free_room_table_set'][$colunm_name_prefix . ( $j + 1 ) . '_type'] )
					? $this->options['kktt_free_room_table_set'][$colunm_name_prefix . ( $j + 1 ) . '_type']
					: '';

				$value_in_use = $room_info === 'in_use' ? 'checked="checked"' : '';
				$value_in_negotiations = $room_info === 'in_negotiations' ? 'checked="checked"' : '';
				$value_now_free = $room_info === 'now_free' ? 'checked="checked"' : '';
				$value_default = empty( trim( $room_info ) ) ? 'checked="checked"' : '';
				$value_home = $room_type === 'type_home' ? 'checked="checked"' : '';
				$value_storage = $room_type === 'type_storage' ? 'checked="checked"' : '';
				$value_type_default = empty( trim( $room_type ) ) ? 'checked="checked"' : '';

				$html .= '<tr>' . "\n";
				$html .= '<td>';
				$html .= '<p class="sell_name">' . $colunm_name_prefix . sprintf( '%02d', ( $j + 1 ) ) . '</p>';
				$html .= '<input type="radio" id="' . $colunm_name_prefix . ( $j + 1 ) . '-1" name="kktt_free_room_announce_settings[kktt_free_room_table_set][' . $colunm_name_prefix . sprintf( '%02d', ( $j + 1 ) ) . ']" value="in_use" ' . $value_in_use . '>' . "\n";
				$html .= '<label class="room_label" for="' . $colunm_name_prefix . ( $j + 1 ) . '-1">契約中</label>';
				$html .= '<input type="radio" id="' . $colunm_name_prefix . ( $j + 1 ) . '-2" name="kktt_free_room_announce_settings[kktt_free_room_table_set][' . $colunm_name_prefix . sprintf( '%02d', ( $j + 1 ) ) . ']" value="in_negotiations" ' . $value_in_negotiations . '>' . "\n";
				$html .= '<label class="room_label" for="' . $colunm_name_prefix . ( $j + 1 ) . '-2">商談中</label>';
				$html .= '<input type="radio" id="' . $colunm_name_prefix . ( $j + 1 ) . '-3" name="kktt_free_room_announce_settings[kktt_free_room_table_set][' . $colunm_name_prefix . sprintf( '%02d', ( $j + 1 ) ) . ']" value="now_free" ' . $value_now_free . $value_default . '>' . "\n";
				$html .= '<label class="room_label" for="' . $colunm_name_prefix . ( $j + 1 ) . '-3">空き</label>';
				$html .= '<input id="' . $colunm_name_prefix . ( $j + 1 ) . '_company" type="text" name="kktt_free_room_announce_settings[kktt_free_room_table_set][' . $colunm_name_prefix . ( $j + 1 ) . '_company_name]" value="' . esc_html( $company_name ) . '">';
				$html .= '<p class="terminal_type">ホーム/保管庫区分</p>';
				$html .= '<input type="radio" id="' . $colunm_name_prefix . ( $j + 1 ) . '-1_type" name="kktt_free_room_announce_settings[kktt_free_room_table_set][' . $colunm_name_prefix . ( $j + 1 ) . '_type]" value="type_home" ' . $value_home . $value_type_default . '>' . "\n";
				$html .= '<label class="room_label" for="' . $colunm_name_prefix . ( $j + 1 ) . '-1_type">ホーム</label>';
				$html .= '<input type="radio" id="' . $colunm_name_prefix . ( $j + 1 ) . '-2_type" name="kktt_free_room_announce_settings[kktt_free_room_table_set][' . $colunm_name_prefix . ( $j + 1 ) . '_type]" value="type_storage" ' . $value_storage . '>' . "\n";
				$html .= '<label class="room_label" for="' . $colunm_name_prefix . ( $j + 1 ) . '-2_type">保管庫</label>';
				$html .= '</td>' . "\n";
				$html .= '</tr>' . "\n";

			}

			$html .= '</tbody>' . "\n";
			$html .= '</table>' . "\n";
			$html .= '</div><!-- / .each_repository -->' . "\n";

		}

		$html .= '</div><!-- / .repository_outer -->' . "\n";

        echo $html;

	}

	public function email_template_field_render() {

		$kktt_free_room_settings = $this->options;

	}

	/**
	 * Output email list display at plugin admin panel.
	 */
	public function kktt_cell_relation_email_render() {

		$args = array(
			'post_type' => 'flamingo_inbound',
		);
		$flamingo_dates = get_posts( $args );
		$flamingo_dates_count = count( $flamingo_dates );

		$key_arr = array();
		$mail_arr = array();
		$post_id_arr = array();

		for ( $i = 0; $i < $flamingo_dates_count; $i++ ) {

			$post_id = $flamingo_dates[$i]->ID;
			$your_email = get_post_meta( $post_id, '_field_your-email', true );
			$your_name = get_post_meta( $post_id, '_field_your-name', true )
                ? get_post_meta( $post_id, '_field_your-name', true )
                : '';
			$company_organization_name = get_post_meta( $post_id, 'company-organization-name', true )
                ? get_post_meta( $post_id, 'company-organization-name', true )
                : '';

			$your_waiting = get_post_meta( $post_id, '_field_your-waiting', true )
                ? get_post_meta( $post_id, '_field_your-waiting', true )
                : '';

			$terms = wp_get_post_terms( $post_id, 'flamingo_inbound_channel' );

			foreach ( $terms as $term ) {

			    $term_name = $term->name;
			    if ( ! empty( $term ) && ! is_wp_error( $term ) && $term_name === 'お知らせ配信停止フォーム' ) {

			        $this->delete_flag = true;
			        $this->delete_address = get_post_meta( $post_id, '_field_your-email', true );
			        $args = array(
			            'post_type'       => 'flamingo_inbound',
                        'meta_key'        => '_field_your-email',
                        'meta_query'      => array(
                            array(
                                'key'     => '_field_your-email',
                                'value'   => $this->delete_address,
                                'compare' => '=',
                            ),
                        ),
                    );
			        $delete_posts = get_posts( $args );

				    /**
				     * Delete post include stop notification request email address and set flag false.
                     * Set flag = false, because through unset function unset( $mail[ $delete_index ] );
				     */
			        if ( ! empty( $delete_posts ) ) {

			            foreach ( $delete_posts as $delete_post ) {

				            wp_delete_post( $delete_post->ID );

                        }
                        $this->delete_flag = false; // yes!!

                    }

                }

            }

			$your_waiting = explode( ',', $your_waiting );
			$your_waiting_count = count( $your_waiting );

			if ( ! empty( $your_waiting ) && ! empty( $your_email ) ) {

				for ( $j = 0; $j < $your_waiting_count; $j++ ) {

					$key_arr[] = $your_waiting[$j];
					$mail_arr[] = $your_email;
					$post_id_arr[] = $post_id;

				}

			}

		}

		$total_arr = array();
		$key_arr_count = count( $key_arr );
		for ( $h = 0; $h < $key_arr_count; $h++ ) {

			if ( ! array_key_exists( $key_arr[$h], $total_arr ) ) {

				$total_arr[ $key_arr[$h] ]['email'] = (array) $mail_arr[ $h ];

			} else {

			    if ( ! in_array( $mail_arr[ $h ], $total_arr[ $key_arr[$h] ]['email'] ) ) {

				    $total_arr[ $key_arr[ $h ] ]['email'][] = $mail_arr[ $h ];

			    }

			}

			$total_arr[ $key_arr[$h]]['post_id'] = $post_id_arr[ $h ];

		}

		$sort_flag = ksort( $total_arr );

		$html = '';
		if ( $sort_flag === true ) {
			$html .= '<div class="waiting_mail_list">';
			$html .= '<table>';
			$html .= '<tbody>';
			$html .= '<tr>';
			$html .= '<th>';
			$html .= 'ブロック名';
			$html .= '</th>';
			$html .= '<th>';
			$html .= '連絡待ちのメールアドレス';
			$html .= '</th>';
			$html .= '</tr>';

			foreach ( $total_arr as $key => $data ) {

				$email = $this->get_mail_arr( $data['email'], $this->delete_flag, $this->delete_address );
				$mail_str = implode( ',', $email );
				$post_id = $data['post_id'];

				if ( ! empty( $key ) && ! empty( $email ) ) {

					$html .= '<tr>';
					$html .= '<td>';
					$html .= esc_html( $key );
					$html .= '</td>';
					$html .= '<td>';
					$html .= $mail_str;
					$html .= '</td>';
					$html .= '</tr>';

				}

				// status check
                $cell_data_status = isset( $this->options['kktt_free_room_table_set'][$key] )
                    ? $this->options['kktt_free_room_table_set'][$key]
                    : '';

                $mail = array_values( $email );
                // Call send_email
                $this->send_email( $company_organization_name, $your_name, $cell_data_status, $key, $email, $post_id, $this->delete_flag );

			}

			$html .= '</tbody>';
			$html .= '</table>';
			$html .= '</div><!-- / .waiting_mail_list -->';

		}

		echo $html;

		//var_dump( $this->send_flag );

	}

	/**
	 * Output Sender e-mail field.
	 */
	public function kktt_from_email_render() {

		$sender_email = isset( $this->options['kktt_sender_email_field'] )
            ? trim( sanitize_email( $this->options['kktt_sender_email_field'] ) )
            : '';

		?>

        <input type="text" name="kktt_free_room_announce_settings[kktt_sender_email_field]"
               value="<?php echo esc_html( $sender_email ); ?>" size="50" maxlength="999">

		<?php

	}


	/**
     * Get mail array for send.
	 * @param $mail
	 *
	 * @return array
	 */
	public function get_mail_arr( $mail, $delete_flag, $delete_address ) {

	    $mail = array_map( 'sanitize_email', $mail );
        $email = implode( ',', $mail );

        if ( $delete_flag === true ) {

	        $delete_index = array_search( $delete_address, $mail );
	        unset( $mail[ $delete_index ] );
	        $mail = array_values( (array) $mail );

        }

		return $mail;

    }

    public function get_mail_address_for_stop_sending( $post_id ) {

	    $stop_sending_address = $post_id
            ? get_post_meta( $post_id, '_field_your-email', true )
            : '';
	    return $stop_sending_address;

    }

	public function send_email( $company_organization_name, $your_name, $cell_data_status, $key, $mail, $post_id, $flag ) {

		if ( $cell_data_status === 'now_free' ) {

			$message  = '';
			$message .= esc_attr( $company_organization_name . ' ' . $your_name ) . '様';
			$message .= '空き状況お知らせメールに登録されました、' . "\n\n";
			$message .= esc_attr( $key ) . "\n\n";
			$message .= 'に空きが出ました。' . "\n";
			$message .= '空き状況一覧(';
			$message .= home_url( '/availability-list' ) . ')' . "\n";
			$message .= 'をご確認くださいますようお願い申し上げます。' . "\n";
			$message .= '/******************************
鹿児島県共同トラックターミナル株式会社
電話 : 
e-mail : 
Web : 
******************************/';

			$sender_email = isset( $this->options['kktt_sender_email_field'] )
				? $this->options['kktt_sender_email_field']
				: get_option( 'admin_email' );
			$subject = '【鹿児島県共同トラックターミナル】空きが出ました';
			//$message = esc_attr( $key ) . '空きになりました。';
			$headers[] = 'From:' . sanitize_email( $sender_email );
			$attachments[] = '';

			foreach ( (array) $mail as $email ) {

				add_filter( 'wp_mail_from', function( $sender_email ) { return sanitize_email( $sender_email ); }, 99999 );

				$send_to_waiting = wp_mail( $email, $subject, $message, $headers, $attachments );

				if ( $send_to_waiting === true ) {

					/**
					 * Delete flamingo_inbound post your-waiting custom field's cell name.
					 * It's depend on parameter "$post_id".
					 *
					 */
					$target_post = get_post( $post_id );
					$waiting_cell_datas = get_post_meta( $post_id, '_field_your-waiting', true );
					$waiting_cell_datas = explode( ',', $waiting_cell_datas );

					if ( in_array( $key, $waiting_cell_datas ) ) {

						$before_meta = get_post_meta( $post_id, '_field_your-waiting', $key );
						$before_meta_to_array = explode( ',', $before_meta );

					if ( in_array( $key, $before_meta_to_array ) ) {

						$array_position = array_search( $key, $before_meta_to_array );
						unset( $before_meta_to_array[ $array_position ]);

                    }
						$unset_key_arr = array_values( $before_meta_to_array );
						$unset_key__str = implode( ',', $unset_key_arr );
						update_post_meta( $post_id, '_field_your-waiting', $unset_key__str );

					}

				}

			}

		}

    }

	/**
     * Display cell and waiting email table list at front page.
	 * @param $atts
	 *
	 * @return string
	 */
	public function add_terminal_table( $atts ) {
		global $post;
		extract( shortcode_atts( array(

		), $atts ) );

		$html  = '';
		$html .= '<div class="repository_outer">' . "\n";

		for ( $i = 0; $i < $this->repository_num; $i++ ) {

			$html .= '<div class="each_repository">' . "\n";
			$html .= '<table>' . "\n";
			$html .= '<tbody>' . "\n";

			for ( $j = 0; $j < $this->max_column; $j ++ ) {

				switch ( $i ) {

					case 0 :
						$colunm_name_prefix = 'a-';
						break;

					case 1 :
						$colunm_name_prefix = 'b-';
						break;

					case 2 :
						$colunm_name_prefix = 'c-';
						break;

					case 3 :
						$colunm_name_prefix = 'd-';
						break;

				}

				$room_info = isset( $this->options['kktt_free_room_table_set'][$colunm_name_prefix . sprintf( '%02d', ( $j + 1 ) )] )
                    ? $this->options['kktt_free_room_table_set'][$colunm_name_prefix . sprintf( '%02d', ( $j + 1 ) )]
                    : 'now_free';
				$company_name = isset( $this->options['kktt_free_room_table_set'][$colunm_name_prefix . ( $j + 1 ) . '_company_name'] )
					? $this->options['kktt_free_room_table_set'][$colunm_name_prefix . ( $j + 1 ) . '_company_name']
					: '';
				$room_type = isset( $this->options['kktt_free_room_table_set'][$colunm_name_prefix . ( $j + 1 ) . '_type'] )
					? $this->options['kktt_free_room_table_set'][$colunm_name_prefix . ( $j + 1 ) . '_type']
					: '';
				$cell_home_class = 'kktt_' . esc_attr( $room_info );
				$cell_home_inside_class = 'inside_' . esc_attr( $room_info );
				$cell_type_class = 'kktt_' . esc_attr( $room_type );
				$cell_type_inside_class = 'inside_' . esc_attr( $room_type );

				$html .= '<tr>' . "\n";
				$html .= '<td class="' . $cell_home_class . ' ' . $cell_type_class . ' cell_datas">';
				$html .= '<div class="' . $cell_home_inside_class . ' ' . $cell_type_inside_class . ' cell_inside">';
				$html .= '<span class="cell_name">' . $colunm_name_prefix . sprintf( '%02d', ( $j + 1 ) ) . '</span>';

				switch ( $room_info ) {

                    case 'in_use' :
                        $room_status = '';
                        break;
                    case 'in_negotiations' :
                        $room_status = '商談中';
                        break;
                    case 'now_free' :
                        $room_status = '空きあり';
                        break;

                    default :
	                    $room_status = '空きあり';
                        break;

                }

                $html .= '<span class="cell_status">' . esc_attr( $room_status ) . '</span>';

                $html .= '</div>';
				$html .= '</td>' . "\n";
				$html .= '</tr>' . "\n";

			}

			$html .= '</tbody>' . "\n";
			$html .= '</table>' . "\n";
			$html .= '</div><!-- / .each_repository -->' . "\n";

		}

		$html .= '</div><!-- / .repository_outer -->' . "\n";

		$html .= '<div class="room_examination">';
		$html .= '<ul class="room_examination__list">';

		$html .= '<li class="ex_now_in_use cell_explanation__list_item">契約済み</li>';
		$html .= '<li class="ex_in_negotiations cell_explanation__list_item">商談中</li>';
		$html .= '<li class="ex_now_free cell_explanation__list_item">空きあり</li>';

		$html .= '</ul>';
		$html .= '</div>';

		return $html;

	}

	/**
	 * Output KKTT free room announce page form.
	 */
	public function kktt_free_room_announce_options_page() {

		?>
		<form action='options.php' method='post'>

			<?php
			settings_fields( 'kkttfreeannouncepage' );
			do_settings_sections( 'kkttfreeannouncepage' );
			submit_button();
			?>

		</form>
		<?php
	}

}

