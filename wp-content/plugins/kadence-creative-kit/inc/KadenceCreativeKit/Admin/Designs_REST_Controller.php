<?php
/**
 * The provider for all rest controller related functionality.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CreativeKit
 */

namespace KadenceWP\CreativeKit\Admin;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;
use KadenceWP\CreativeKit\Admin\PatternContentAnalyzer;

use function KadenceWP\KadenceBlocks\StellarWP\Uplink\get_authorization_token;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\get_license_domain;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\get_original_domain;
use function KadenceWP\KadenceBlocks\StellarWP\Uplink\is_authorized;
use function kadence_blocks_get_current_license_data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The provider for all rest controller related functionality.
 *
 * @since 0.1.0
 *
 * @package KadenceWP\CreativeKit
 */
class Designs_REST_Controller extends WP_REST_Controller {

    private $suggest_endpoint = 'https://content.startertemplatecloud.com/wp-json/prophecy/v1/suggest/layout';

    private $create_endpoint = 'https://content.startertemplatecloud.com/wp-json/prophecy/v1/content/create_page';

    private $styles_endpoint = 'https://patterns.startertemplatecloud.com/wp-json/kadence-cloud/v1/page-wizard';

    private $job_endpoint = 'https://content.startertemplatecloud.com/wp-json/prophecy/v1/content/job/';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->namespace = 'kadence-creative-kit/v1';
        $this->rest_base = 'get';
    }

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/suggest',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'get_suggested_layout' ],
					'permission_callback' => [ $this, 'get_standard_permissions_check' ],
				],
			]
		);

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/styles',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'get_styles_from_structure' ],
                    'permission_callback' => [ $this, 'get_standard_permissions_check' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/create_page',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_page' ],
                    'permission_callback' => [ $this, 'get_standard_permissions_check' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/job',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'get_job' ],
                    'permission_callback' => [ $this, 'get_standard_permissions_check' ],
                ],
            ]
        );
	}

    /**
     * @param $request
     * @return mixed
     */
    public function get_standard_permissions_check($request ) {
        return true;
        return current_user_can( 'manage_options' );
    }

    public function get_styles_from_structure( $request ) {
        $structure = $request->get_param( 'page_structure' );

        if( empty( $structure ) || ! is_array( $structure ) ) {
            return new WP_Error( 'rest_missing_required_param', __( 'Missing required parameter.', 'kadence-creative-kit' ), [ 'status' => 400 ] );
        }

        $normalize_structure = implode(',', $structure );
        $normalize_structure = strtolower( str_replace( '_', '-', $normalize_structure ) );

        $args = [
            'key' => 'section',
            'structure' => $normalize_structure
        ];

        $response = wp_remote_get( add_query_arg( $args, $this->styles_endpoint ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'rest_error', __( 'Error fetching data from Prophecy.', 'kadence-creative-kit' ), [ 'status' => 500 ] );
        }

        $body = wp_remote_retrieve_body( $response );

        $response_code = wp_remote_retrieve_response_code( $response );
        if( 200 !== $response_code ) {
            return new WP_Error( 'rest_error', __( 'Error fetching data from Prophecy.', 'kadence-creative-kit' ), [ 'status' => $response_code ] );
        }

        $data = json_decode( $body, true );

        $styles = [];
        foreach( $data as $key => $value ) {
            $styles[$key] = $value;
        }

        return rest_ensure_response( [ 'success' => true, 'data' => $styles ] );
    }

	/**
	 * Get suggested layout from prophecy.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_suggested_layout( $request ) {

        $required_fields = [ 'page_id', 'page_title', 'page_context' ];

        foreach ( $required_fields as $field ) {
            if ( empty( $request->get_param( $field ) ) ) {
                return new WP_Error( 'rest_missing_required_param', __( 'Missing required parameter.', 'kadence-creative-kit' ), [ 'status' => 400 ] );
            }
        }

        $prophecy_data = json_decode( get_option( 'kadence_blocks_prophecy' ), true );
        $required_prophecy_data = [ 'industry', 'location', 'missionStatement', 'keywords', 'companyName' ];

        foreach ( $required_prophecy_data as $field ) {
            if ( empty( $prophecy_data[ $field ] ) ) {
                return new WP_Error( 'rest_missing_required_prophecy_data', __( 'Missing required AI data.', 'kadence-creative-kit' ), [ 'status' => 400 ] );
            }
        }

        if( strlen( $request->get_param( 'page_title' ) ) < 5) {
            return new WP_Error( 'rest_error', __( 'Page title must be at least 5 characters long.', 'kadence-creative-kit' ), [ 'status' => 400 ] );
        }

        $args = [
            'page_id' => $request->get_param( 'page_id' ),
            'page_title' => $request->get_param( 'page_title' ),
            'page_context' => $request->get_param( 'page_context' ),
            'industry' => $prophecy_data['industry'],
            'location' => $prophecy_data['location'],
            'mission' => $prophecy_data['missionStatement'],
            'keywords' => $prophecy_data['keywords'],
            'company' => $prophecy_data['companyName'],
        ];

        $response = wp_remote_post( $this->suggest_endpoint, [
            'body' => $args,
            'headers'  => array(
                'X-Prophecy-Token' => $this->get_prophecy_token_header()
            ),
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'rest_error', __( 'Error fetching data from Prophecy.', 'kadence-creative-kit' ), [ 'status' => 500 ] );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        $response_code = wp_remote_retrieve_response_code( $response );

        if ( 200 !== $response_code && !empty( $data['data']['code'] ) && !empty( $data['data']['message'] ) ) {
            return new WP_Error( $data['data']['code'], $data['data']['message'], [ 'status' => $response_code ] );
        } else if ( 200 !== $response_code ) {
            return new WP_Error( 'rest_error', __( 'Error fetching data from Prophecy.', 'kadence-creative-kit' ), [ 'status' => $response_code ] );
        }

        if ( empty( $data ) || empty( $data['data'] ) || empty( $data['data']['choices']) ) {
            return new WP_Error( 'rest_error', __( 'Empty data from Prophecy.', 'kadence-creative-kit' ), [ 'status' => 500 ] );
        }

        return rest_ensure_response( [ 'success' => true, 'status' => $response_code , 'data' => $data['data'] ] );
	}

    /**
     * Send create page request to prophecy. This will queue a job we can check on later.
     *
     * @param $request
     * @return WP_Error
     */
    public function create_page($request ) {
        $patterns = $request->get_param( 'patterns' );

        if ( empty( $patterns ) ) {
            return new WP_Error( 'rest_missing_required_param', __( 'Missing required parameter.', 'kadence-creative-kit' ), [ 'status' => 400 ] );
        }

        $patternContent = new PatternContentAnalyzer( $patterns );
        $requiredContent = $patternContent->getRequiredContent();

        $prophecy_data = json_decode( get_option( 'kadence_blocks_prophecy' ), true );
        $body = [
            'context' => 'kadence'
        ];

        $body['page_id'] =  'test-123';
        $body['page_title'] = 'Contact Us';
        $body['page_context'] = 'Contact our hotdog stand. Where to find us, our email, phone number, along with some reviews. A contact form to send a submission too';
        $body['prompts'] = $requiredContent;

        $body['company'] = ! empty( $prophecy_data['companyName'] )	? $prophecy_data['companyName'] : '';
        if ( ! empty( $prophecy_data['industrySpecific'] ) && 'Other' !== $prophecy_data['industrySpecific'] ) {
            $body['industry'] = ! empty( $pentity_typerophecy_data['industrySpecific'] ) ? $prophecy_data['industrySpecific'] : '';
        } elseif ( ! empty( $prophecy_data['industrySpecific'] ) && 'Other' === $prophecy_data['industrySpecific'] && ! empty( $prophecy_data['industryOther'] ) ) {
            $body['industry'] = ! empty( $prophecy_data['industryOther'] ) ? $prophecy_data['industryOther'] : '';
        } elseif ( ! empty( $prophecy_data['industry'] ) && 'Other' === $prophecy_data['industry'] && ! empty( $prophecy_data['industryOther'] ) ) {
            $body['industry'] = ! empty( $prophecy_data['industryOther'] ) ? $prophecy_data['industryOther'] : '';
        } else {
            $body['industry'] = ! empty( $prophecy_data['industry'] ) ? $prophecy_data['industry'] : '';
        }
        $body['location'] = ! empty( $prophecy_data['location'] ) ? $prophecy_data['location'] : '';
        $body['mission'] = ! empty( $prophecy_data['missionStatement'] ) ? $prophecy_data['missionStatement'] : '';
        $body['tone'] = ! empty( $prophecy_data['tone'] ) ? $prophecy_data['tone'] : '';
        $body['keywords'] = ! empty( $prophecy_data['keywords'] ) ? $prophecy_data['keywords'] : '';
        $body['entity_type'] = 'COMPANY';
//        $body['lang'] = ! empty( $prophecy_dataentity_type['lang'] ) ? $prophecy_data['lang'] : '';

        $response = wp_remote_post(
            $this->create_endpoint,
            array(
                'timeout' => 20,
                'headers' => array(
                    'X-Prophecy-Token' => $this->get_prophecy_token_header(),
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
            $contents = wp_remote_retrieve_body( $response );
            if ( ! empty( $contents ) && is_string( $contents ) && json_decode( $contents, true ) ) {
                $error_message = json_decode( $contents, true );
                if ( ! empty( $error_message['detail'] ) && 'Failed, unable to use credits.' === $error_message['detail'] ) {
                    return 'credits';
                }
            }
            return 'error1';
        }

        $contents = wp_remote_retrieve_body( $response );
        // Early exit if there was an error.
        if ( is_wp_error( $contents ) ) {
            return 'error2';
        }

        $body = json_decode( $contents, true );
        $job_id = !empty( $body['data']['job_id'] ) ? $body['data']['job_id'] : 0;

        return rest_ensure_response( [ 'success' => true, 'status' => 200, 'prophecy_response' => $contents, 'required_content' => $requiredContent, 'job_id' => $job_id, 'body' => $body ] );
    }

    /**
     * Gets data from page_create job.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function get_job( $request ) {
        $job_id = $request->get_param( 'job_id' );

        if( empty( $job_id ) || !is_numeric( $job_id ) ) {
            return new WP_Error( 'rest_missing_required_param', __( 'Missing required parameter.', 'kadence-creative-kit' ), [ 'status' => 400 ] );
        }

        $response = wp_remote_get(
            $this->job_endpoint . $job_id . '/?type=full_page_content',
            array(
                'timeout' => 20,
                'headers' => array(
                    'X-Prophecy-Token' => $this->get_prophecy_token_header(),
                    'Content-Type' => 'application/json',
                )
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'rest_error', __( 'There was an error during the request.', 'kadence-creative-kit' ), [ 'status' => 400, '$response' => $response ] );
        }

        $contents = wp_remote_retrieve_body( $response );
        // Early exit if there was an error.
        if ( is_wp_error( $contents ) ) {
            return 'error2';
        }

        $body = json_decode( $contents, true );
        $patterns = !empty( $body['patterns'] ) ? $body['patterns'] : [];
        $status = !empty( $body['job_status'] ) ? $body['job_status'] : '';
        $complete = $status !== 'retry' && count( $patterns ) > 0;
        $failed = $status === 'failed';

        return rest_ensure_response( [ 'success' => true, 'status' => 200, 'complete' => $complete, 'failed' => $failed, 'patterns' => $patterns ] );
    }

    /**
     * Constructs a consistent X-Prophecy-Token header.
     *
     * @param array $args An array of arguments to include in the encoded header.
     *
     * @return string The base64 encoded string.
     */
    public function get_prophecy_token_header( $args = [] ) {
        $site_url     = get_original_domain();
        $site_name    = get_bloginfo( 'name' );
        $license_data = $this->get_current_license_data();
		$product_slug = ( ! empty( $license_data['product'] ) ? $license_data['product'] : 'kadence-blocks' );
        $defaults = [
            'domain'          => $site_url,
            'key'             => ! empty( $license_data['key'] ) ? $license_data['key'] : '',
            'site_name'       => sanitize_title( $site_name ),
            'product_slug'    => apply_filters( 'kadence-blocks-auth-slug', $product_slug ),
            'product_version' => KADENCE_CREATIVE_KIT_VERSION,
        ];

        $parsed_args = wp_parse_args( $args, $defaults );

        return base64_encode( json_encode( $parsed_args ) );
    }

    /**
     * Check if a response code is an error.
     *
     * @access public
     * @return string Returns the remote URL contents.
     */
    public function is_response_code_error( $response ) {
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        if ( $response_code >= 200 && $response_code < 300 ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the current license key for the plugin.
     *
     * @return string
     */
    public function get_current_license_data() {

        if ( function_exists( 'kadence_blocks_get_current_license_data' ) ) {
            $data = kadence_blocks_get_current_license_data();
            return $data;
        } else {
            $license_data = [
                'key'     => get_license_key( 'kadence-creative-kit' ),
                'email'   => '',
                'product' => 'kadence-creative-kit',
            ];
            return $license_data;
        }
    }
}