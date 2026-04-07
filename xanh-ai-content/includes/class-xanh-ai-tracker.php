<?php
/**
 * Token Usage Tracker — persistent per-model & per-day token tracking.
 *
 * Stores monthly usage data in wp_options as JSON,
 * grouped by model ID and by day to power dashboard charts.
 *
 * @package Xanh_AI_Content
 * @since   1.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xanh_AI_Tracker {

	/**
	 * Gemini API pricing (USD per 1M tokens) — Pay-as-you-go tier.
	 * Free tier is $0 but has rate limits.
	 * @see https://ai.google.dev/pricing
	 */
	private const PRICING = [
		'gemini-2.5-flash' => [
			'input'  => 0.15,  // $0.15 / 1M input tokens
			'output' => 0.60,  // $0.60 / 1M output tokens (non-thinking)
		],
		'gemini-2.5-pro' => [
			'input'  => 1.25,
			'output' => 10.00,
		],
		'gemini-2.0-flash' => [
			'input'  => 0.10,
			'output' => 0.40,
		],
		'gemini-1.5-flash' => [
			'input'  => 0.075,
			'output' => 0.30,
		],
		'gemini-1.5-pro' => [
			'input'  => 1.25,
			'output' => 5.00,
		],
		// Image models — flat cost per image.
		'gemini-3.1-flash-image-preview' => [
			'per_image' => 0.039, // ~$0.039/image
		],
	];

	/** USD to VND approximate rate. */
	private const USD_TO_VND = 25500;

	/*--------------------------------------------------------------
	 * Record Usage
	 *------------------------------------------------------------*/

	/**
	 * Record API usage for a specific model.
	 *
	 * @param string $model_id    Model identifier (e.g. 'gemini-2.5-flash').
	 * @param string $type        Usage type: 'text' or 'image'.
	 * @param int    $input_tokens  Number of input/prompt tokens.
	 * @param int    $output_tokens Number of output/candidate tokens.
	 */
	public static function record_usage( string $model_id, string $type, int $input_tokens = 0, int $output_tokens = 0 ): void {
		$key  = self::month_key();
		$data = get_option( $key, [] );

		if ( ! is_array( $data ) ) {
			$data = [];
		}

		if ( ! isset( $data['models'] ) || ! is_array( $data['models'] ) ) {
			$data['models'] = [];
		}

		if ( ! isset( $data['models'][ $model_id ] ) ) {
			$data['models'][ $model_id ] = [
				'text_input'  => 0,
				'text_output' => 0,
				'image_calls' => 0,
				'api_calls'   => 0,
			];
		}

		$model = &$data['models'][ $model_id ];

		if ( 'image' === $type ) {
			$model['image_calls'] = ( $model['image_calls'] ?? 0 ) + 1;
		} else {
			$model['text_input']  = ( $model['text_input'] ?? 0 ) + $input_tokens;
			$model['text_output'] = ( $model['text_output'] ?? 0 ) + $output_tokens;
		}

		$model['api_calls'] = ( $model['api_calls'] ?? 0 ) + 1;

		$data['total_api_calls'] = ( $data['total_api_calls'] ?? 0 ) + 1;
		$data['last_updated']    = current_time( 'c' );

		// --- Daily tracking for charts ---
		$day = gmdate( 'd' );

		if ( ! isset( $data['daily'] ) || ! is_array( $data['daily'] ) ) {
			$data['daily'] = [];
		}

		if ( ! isset( $data['daily'][ $day ] ) ) {
			$data['daily'][ $day ] = [
				'text_input'  => 0,
				'text_output' => 0,
				'image_calls' => 0,
				'api_calls'   => 0,
			];
		}

		$daily = &$data['daily'][ $day ];

		if ( 'image' === $type ) {
			$daily['image_calls'] = ( $daily['image_calls'] ?? 0 ) + 1;
		} else {
			$daily['text_input']  = ( $daily['text_input'] ?? 0 ) + $input_tokens;
			$daily['text_output'] = ( $daily['text_output'] ?? 0 ) + $output_tokens;
		}

		$daily['api_calls'] = ( $daily['api_calls'] ?? 0 ) + 1;

		update_option( $key, $data, false );
	}

	/*--------------------------------------------------------------
	 * Read Data
	 *------------------------------------------------------------*/

	/**
	 * Get usage data for a specific month.
	 *
	 * @param string $year_month 'YYYY_MM' format. Default: current month.
	 * @return array Usage data.
	 */
	public static function get_month( string $year_month = '' ): array {
		if ( empty( $year_month ) ) {
			$year_month = gmdate( 'Y_m' );
		}

		$data = get_option( 'xanh_ai_usage_' . $year_month, [] );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Get summary across recent months.
	 *
	 * @param int $months Number of months to include.
	 * @return array Array of [ 'YYYY_MM' => data ].
	 */
	public static function get_summary( int $months = 6 ): array {
		$result = [];

		for ( $i = 0; $i < $months; $i++ ) {
			$ym          = gmdate( 'Y_m', strtotime( "-{$i} months" ) );
			$result[ $ym ] = self::get_month( $ym );
		}

		return $result;
	}

	/**
	 * Get aggregated usage data for a date range.
	 *
	 * Reads daily data across relevant months and aggregates.
	 *
	 * @param string $start_date 'Y-m-d' format.
	 * @param string $end_date   'Y-m-d' format.
	 * @return array {
	 *     models:          { model_id: { text_input, text_output, image_calls, api_calls } },
	 *     total_api_calls: int,
	 *     daily:           { 'Y-m-d': { text_input, text_output, image_calls, api_calls } },
	 *     last_updated:    string,
	 * }
	 */
	public static function get_range( string $start_date, string $end_date ): array {
		$start = new \DateTime( $start_date );
		$end   = new \DateTime( $end_date );
		$end->modify( '+1 day' ); // Make end inclusive.

		// Determine which months to load.
		$months_needed = [];
		$cursor        = clone $start;
		while ( $cursor < $end ) {
			$ym = $cursor->format( 'Y_m' );
			if ( ! in_array( $ym, $months_needed, true ) ) {
				$months_needed[] = $ym;
			}
			$cursor->modify( '+1 month' );
		}
		// Also include the end month.
		$end_ym = ( clone $end )->modify( '-1 day' )->format( 'Y_m' );
		if ( ! in_array( $end_ym, $months_needed, true ) ) {
			$months_needed[] = $end_ym;
		}

		$result = [
			'models'          => [],
			'total_api_calls' => 0,
			'daily'           => [],
			'last_updated'    => '',
		];

		foreach ( $months_needed as $ym ) {
			$month_data  = self::get_month( $ym );
			$daily       = $month_data['daily'] ?? [];
			$models_data = $month_data['models'] ?? [];

			// Parse year/month.
			$parts = explode( '_', $ym );
			$year  = (int) $parts[0];
			$month = (int) $parts[1];

			// Iterate daily data and check if each day falls within range.
			foreach ( $daily as $day_num => $day_data ) {
				$date_str = sprintf( '%04d-%02d-%02d', $year, $month, (int) $day_num );
				$date_obj = new \DateTime( $date_str );

				if ( $date_obj >= $start && $date_obj < $end ) {
					$result['daily'][ $date_str ] = $day_data;
					$result['total_api_calls']   += $day_data['api_calls'] ?? 0;
				}
			}

			// For model-level aggregation, we need to check if the ENTIRE month
			// falls within range. If partial, we can only use daily data for accuracy.
			// For simplicity (and because model-level daily tracking doesn't exist),
			// we aggregate model data proportionally or fully if month is within range.
			if ( ! empty( $daily ) ) {
				// Count days in range for this month.
				$days_in_range = 0;
				$total_days    = 0;
				foreach ( $daily as $day_num => $day_data ) {
					$total_days++;
					$date_str = sprintf( '%04d-%02d-%02d', $year, $month, (int) $day_num );
					$date_obj = new \DateTime( $date_str );
					if ( $date_obj >= $start && $date_obj < $end ) {
						$days_in_range++;
					}
				}

				$ratio = $total_days > 0 ? $days_in_range / $total_days : 0;

				foreach ( $models_data as $model_id => $model_usage ) {
					if ( ! isset( $result['models'][ $model_id ] ) ) {
						$result['models'][ $model_id ] = [
							'text_input'  => 0,
							'text_output' => 0,
							'image_calls' => 0,
							'api_calls'   => 0,
						];
					}

					$result['models'][ $model_id ]['text_input']  += (int) round( ( $model_usage['text_input'] ?? 0 ) * $ratio );
					$result['models'][ $model_id ]['text_output'] += (int) round( ( $model_usage['text_output'] ?? 0 ) * $ratio );
					$result['models'][ $model_id ]['image_calls'] += (int) round( ( $model_usage['image_calls'] ?? 0 ) * $ratio );
					$result['models'][ $model_id ]['api_calls']   += (int) round( ( $model_usage['api_calls'] ?? 0 ) * $ratio );
				}
			} elseif ( count( $months_needed ) === 1 ) {
				// No daily data but only one month — use full month data.
				foreach ( $models_data as $model_id => $model_usage ) {
					$result['models'][ $model_id ] = $model_usage;
				}
				$result['total_api_calls'] = $month_data['total_api_calls'] ?? 0;
			}

			// Keep latest last_updated.
			$lu = $month_data['last_updated'] ?? '';
			if ( $lu > $result['last_updated'] ) {
				$result['last_updated'] = $lu;
			}
		}

		// Fallback: if no daily data exists but models have data (pre-upgrade data),
		// synthesize daily entries so the chart shows something.
		if ( empty( $result['daily'] ) && ! empty( $result['models'] ) ) {
			// Determine the best date to place the synthetic bar.
			// Use last_updated if available, otherwise use end of range.
			$synth_date = $end_date;
			if ( ! empty( $result['last_updated'] ) ) {
				try {
					$lu_dt      = new \DateTime( $result['last_updated'] );
					$synth_date = $lu_dt->format( 'Y-m-d' );
				} catch ( \Exception $e ) {
					// Keep end_date.
				}
			}

			// Aggregate all model data into a single daily entry.
			$synth = [
				'text_input'  => 0,
				'text_output' => 0,
				'image_calls' => 0,
				'api_calls'   => 0,
			];
			foreach ( $result['models'] as $m ) {
				$synth['text_input']  += $m['text_input'] ?? 0;
				$synth['text_output'] += $m['text_output'] ?? 0;
				$synth['image_calls'] += $m['image_calls'] ?? 0;
				$synth['api_calls']   += $m['api_calls'] ?? 0;
			}

			$result['daily'][ $synth_date ] = $synth;
		}

		return $result;
	}

	/**
	 * Estimate cost from arbitrary aggregated model data.
	 *
	 * @param array $models { model_id: { text_input, text_output, image_calls } }
	 * @return array { total_usd, total_vnd, by_model }
	 */
	public static function estimate_cost_from_data( array $models ): array {
		$by_model = [];
		$total    = 0.0;

		foreach ( $models as $model_id => $usage ) {
			$cost    = 0.0;
			$pricing = self::PRICING[ $model_id ] ?? null;
			if ( ! $pricing ) {
				foreach ( self::PRICING as $key => $p ) {
					if ( str_starts_with( $model_id, $key ) ) {
						$pricing = $p;
						break;
					}
				}
			}

			if ( $pricing ) {
				if ( isset( $pricing['per_image'] ) ) {
					$cost = ( $usage['image_calls'] ?? 0 ) * $pricing['per_image'];
				} else {
					$input_cost  = ( ( $usage['text_input'] ?? 0 ) / 1_000_000 ) * ( $pricing['input'] ?? 0 );
					$output_cost = ( ( $usage['text_output'] ?? 0 ) / 1_000_000 ) * ( $pricing['output'] ?? 0 );
					$cost        = $input_cost + $output_cost;
				}
			}

			$by_model[ $model_id ] = [
				'usd' => round( $cost, 4 ),
				'vnd' => round( $cost * self::USD_TO_VND ),
			];
			$total += $cost;
		}

		return [
			'total_usd' => round( $total, 4 ),
			'total_vnd' => round( $total * self::USD_TO_VND ),
			'by_model'  => $by_model,
		];
	}

	/*--------------------------------------------------------------
	 * Cost Estimation
	 *------------------------------------------------------------*/

	/**
	 * Estimate cost for a month's usage in both USD and VND.
	 *
	 * @param string $year_month 'YYYY_MM' format. Default: current month.
	 * @return array { total_usd, total_vnd, by_model: { model_id: { usd, vnd } } }
	 */
	public static function estimate_cost( string $year_month = '' ): array {
		$data     = self::get_month( $year_month );
		$models   = $data['models'] ?? [];
		$by_model = [];
		$total    = 0.0;

		foreach ( $models as $model_id => $usage ) {
			$cost = 0.0;

			// Find pricing — try exact match first, then prefix match.
			$pricing = self::PRICING[ $model_id ] ?? null;
			if ( ! $pricing ) {
				foreach ( self::PRICING as $key => $p ) {
					if ( str_starts_with( $model_id, $key ) ) {
						$pricing = $p;
						break;
					}
				}
			}

			if ( $pricing ) {
				if ( isset( $pricing['per_image'] ) ) {
					$cost = ( $usage['image_calls'] ?? 0 ) * $pricing['per_image'];
				} else {
					$input_cost  = ( ( $usage['text_input'] ?? 0 ) / 1_000_000 ) * ( $pricing['input'] ?? 0 );
					$output_cost = ( ( $usage['text_output'] ?? 0 ) / 1_000_000 ) * ( $pricing['output'] ?? 0 );
					$cost        = $input_cost + $output_cost;
				}
			}

			$by_model[ $model_id ] = [
				'usd' => round( $cost, 4 ),
				'vnd' => round( $cost * self::USD_TO_VND ),
			];

			$total += $cost;
		}

		return [
			'total_usd' => round( $total, 4 ),
			'total_vnd' => round( $total * self::USD_TO_VND ),
			'by_model'  => $by_model,
		];
	}

	/*--------------------------------------------------------------
	 * Reset & Export
	 *------------------------------------------------------------*/

	/**
	 * Reset (delete) usage data for a specific month.
	 *
	 * @param string $year_month 'YYYY_MM' format. Default: current month.
	 * @return bool True on success.
	 */
	public static function reset_month( string $year_month = '' ): bool {
		if ( empty( $year_month ) ) {
			$year_month = gmdate( 'Y_m' );
		}

		return delete_option( 'xanh_ai_usage_' . $year_month );
	}

	/**
	 * Export usage data as CSV string.
	 *
	 * @param string $year_month 'YYYY_MM' format. Default: current month.
	 * @return string CSV content.
	 */
	public static function export_csv( string $year_month = '' ): string {
		$data   = self::get_month( $year_month );
		$models = $data['models'] ?? [];
		$cost   = self::estimate_cost( $year_month );

		$lines   = [];
		$lines[] = 'Model,API Calls,Input Tokens,Output Tokens,Image Calls,Cost (USD),Cost (VND)';

		foreach ( $models as $model_id => $usage ) {
			$model_cost = $cost['by_model'][ $model_id ] ?? [ 'usd' => 0, 'vnd' => 0 ];
			$lines[]    = sprintf(
				'%s,%d,%d,%d,%d,%.4f,%d',
				$model_id,
				$usage['api_calls'] ?? 0,
				$usage['text_input'] ?? 0,
				$usage['text_output'] ?? 0,
				$usage['image_calls'] ?? 0,
				$model_cost['usd'],
				$model_cost['vnd']
			);
		}

		// Totals row.
		$lines[] = sprintf(
			'TOTAL,%d,%d,%d,%d,%.4f,%d',
			$data['total_api_calls'] ?? 0,
			array_sum( array_column( $models, 'text_input' ) ),
			array_sum( array_column( $models, 'text_output' ) ),
			array_sum( array_column( $models, 'image_calls' ) ),
			$cost['total_usd'],
			$cost['total_vnd']
		);

		return implode( "\n", $lines );
	}

	/*--------------------------------------------------------------
	 * Helpers
	 *------------------------------------------------------------*/

	/**
	 * Get list of months that have usage data (for month selector).
	 *
	 * @param int $max Number of months to check.
	 * @return array [ 'YYYY_MM' => bool_has_data, ... ]
	 */
	public static function get_available_months( int $max = 12 ): array {
		$result = [];

		for ( $i = 0; $i < $max; $i++ ) {
			$ym   = gmdate( 'Y_m', strtotime( "-{$i} months" ) );
			$data = get_option( 'xanh_ai_usage_' . $ym, [] );
			$result[ $ym ] = ! empty( $data );
		}

		return $result;
	}

	/**
	 * Get current month option key.
	 *
	 * @return string
	 */
	private static function month_key(): string {
		return 'xanh_ai_usage_' . gmdate( 'Y_m' );
	}
}
