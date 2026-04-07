<?php
/**
 * AI Pricing — cost estimation utilities.
 *
 * Extracted from Xanh_AI_Tracker. Contains ONLY pricing constants
 * and cost calculation logic. No data storage.
 *
 * @package Xanh_AI_Content
 * @since   1.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xanh_AI_Pricing {

	/**
	 * Gemini API pricing (USD per 1M tokens) — Pay-as-you-go tier.
	 *
	 * @see https://ai.google.dev/pricing
	 */
	private const PRICING = [
		'gemini-2.5-flash' => [
			'input'  => 0.15,
			'output' => 0.60,
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
			'per_image' => 0.039,
		],
	];

	/** USD to VND approximate rate. */
	private const USD_TO_VND = 25500;

	/**
	 * Estimate cost from aggregated model data.
	 *
	 * @param array $models { model_id: { text_input, text_output, image_calls } }
	 * @return array { total_usd, total_vnd, by_model: { model_id: { usd, vnd } } }
	 */
	public static function estimate_cost( array $models ): array {
		$by_model = [];
		$total    = 0.0;

		foreach ( $models as $model_id => $usage ) {
			$cost    = 0.0;
			$pricing = self::find_pricing( $model_id );

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

	/**
	 * Find pricing for a model ID (exact or prefix match).
	 *
	 * @param string $model_id Model identifier.
	 * @return array|null Pricing array or null.
	 */
	private static function find_pricing( string $model_id ): ?array {
		if ( isset( self::PRICING[ $model_id ] ) ) {
			return self::PRICING[ $model_id ];
		}

		foreach ( self::PRICING as $key => $p ) {
			if ( str_starts_with( $model_id, $key ) ) {
				return $p;
			}
		}

		return null;
	}
}
