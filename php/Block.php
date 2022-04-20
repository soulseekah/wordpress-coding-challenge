<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$attributes = wp_parse_args(
			[
				'className' => '',
			],
			$attributes
		);

		/**
		 * Count all the post types.
		 */
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$counts     = [];

		foreach ( $post_types as $post_type ) {
			$count = wp_count_posts( $post_type->name );
			if ( 'attachment' === $post_type->name ) {
				// Attachments are always inherited as per get_posts.
				$counts[ $post_type->name ] = $count->inherit;
			} else {
				$counts[ $post_type->name ] = $count->publish;
			}
		}

		/**
		 * Get up to 5 posts in foo-baz.
		 */
		$query = new WP_Query(
			[
				'posts_per_page'         => 5,
				'post_type'              => [ 'post', 'page' ],
				'post_status'            => 'any',
				'tag'                    => 'foo',
				'category_name'          => 'baz',
				'post__not_in'           => [ get_the_ID() ],
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'date_query'             => [
					[
						'hour'    => [ 9, 17 ],
						'compare' => 'between',
					],
				],
			]
		);

		ob_start();

		?>
			<div class="<?php echo esc_attr( $attributes['class_name'] ); ?>">
				<h2><?php esc_html_e( 'Post Counts', 'site-counts' ); ?></h2>

				<ul>
				<?php foreach ( $post_types as $post_type ) : ?>
					<li>
					<?php
						echo esc_html(
							sprintf(
								/* translators: %1$d is replaced with the number of entries, %2$s is replaced with the post type general label */
								__( 'There are %1$d %2$s.', 'site-counts' ),
								$counts[ $post_type->name ],
								$post_type->labels->name
							)
						);
					?>
					</li>
				<?php endforeach; ?>
				</ul>

				<p>
				<?php
					echo esc_html(
						sprintf(
							/* translators: %d is replaced with post ID */
							__( 'The current post ID is %d.', 'site-counts' ),
							get_the_ID()
						)
					);
				?>
				</p>

				<?php if ( $query->have_posts() ) : ?>
					<h2>
						<?php
							echo esc_html(
								sprintf(
									/* translators: %1$d is the number of posts, %2$s and %3$s are the name of the tag and category */
									_n(
										'%1$d post with the tag of %2$s and the category of %3$s',
										'%1$d posts with the tag of %2$s and the category of %3$s',
										$query->post_count,
										'site-counts'
									),
									$query->post_count,
									$query->query_vars['tag'],
									$query->query_vars['category_name']
								)
							);
						?>
					</h2>
					<ul>
						<?php while ( $query->have_posts() ) : ?>
							<?php $query->the_post(); ?>
							<li><?php the_title(); ?></li>
						<?php endwhile; ?>
					</ul>
					<?php wp_reset_postdata(); ?>
				<?php endif; ?>
			</div>
		<?php

		return ob_get_clean();
	}
}
