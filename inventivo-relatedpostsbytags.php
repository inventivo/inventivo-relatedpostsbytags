<?php /*
Contributors:   inventivogermany
Plugin Name:    Related Posts by Tags | inventivo
Plugin URI:     https://www.inventivo.de/wordpress-agentur/wordpress-plugins
Description:    Display Related posts by tag
Version:        1.0.0
Author:         Nils Harder
Author URI:     https://www.inventivo.de
Tags: related posts by tag
Requires at least: 3.0
Tested up to:   5.2.2
Stable tag:     1.0.0
Text Domain: inventivo-relatedpostsbytags
Domain Path: /languages
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class InventivoRelatedPosts
{
	public function __construct()
    {
		add_action('wp_enqueue_scripts', array($this,'register_js'));
		add_shortcode('related', array($this,'display_related'));
		add_action('init', array($this,'register_css'));
		add_action('wp_footer', array($this,'print_css'));
	}

	function get_related_data( $post_id, $number_posts = 6, $taxonomy = 'post_tag', $post_type = 'post' )
    {
		global $wpdb;
		$post_id = (int) $post_id;
		$number_posts = (int) $number_posts;

		if($number_posts > 0) {
			$limit = ' LIMIT '.$number_posts;
		} else {
			$limit = ' LIMIT ';
		}

		$related_posts_records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tr.object_id, count( tr.term_taxonomy_id ) AS common_tax_count
         FROM {$wpdb->term_relationships} AS tr
         INNER JOIN {$wpdb->term_relationships} AS tr2 ON tr.term_taxonomy_id = tr2.term_taxonomy_id
         INNER JOIN {$wpdb->term_taxonomy} as tt ON tt.term_taxonomy_id = tr2.term_taxonomy_id
         INNER JOIN {$wpdb->posts} as p ON p.ID = tr.object_id
         WHERE
            tr2.object_id = %d
            AND tt.taxonomy = %s
            AND p.post_type = %s
         GROUP BY tr.object_id
         HAVING tr.object_id != %d
         ORDER BY common_tax_count DESC" . $limit,
				$post_id, $taxonomy, $post_type, $post_id
			)
		);

		if ( count( $related_posts_records ) === 0 )
			return false;

		$related_posts = array();

		foreach( $related_posts_records as $record )
			$related_posts[] = array(
				'post_id' => (int) $record->object_id,
				'common_tax_count' => $record->common_tax_count
			);

		return $related_posts;
	}


	public function display_related($atts)
    {
		global $load_css;
		$load_css = true;

		$number_posts = 6;
		$taxonomy = 'post_tag';
		$post_type = 'post';

		$posts = $this->get_related_data($atts['postid'],$number_posts,$taxonomy,$post_type);

		if( !$posts ) {
			return false;
		}

		foreach($posts as $post) {
			$post_ids[] = $post['post_id'];
			//echo $post['post_id'];
		}
		//print_r($post_ids);

		$defaults = array(
			'post__in' => $post_ids,
			//'post__in' => array('7411','8715'),
			'orderby' => 'post__in',
			'post_type' => array('post'),
			'post_status' => 'publish',
			'posts_per_page' => min( array(count($post_ids), 6)),
			'related_title' => 'Related Posts'
		);
		$options = wp_parse_args( $args, $defaults );

		$related_posts = new WP_Query( $options );


		if( $related_posts->have_posts() ){
			ob_start();
			?>
			<div id="related-posts" class="">
				<div class="col-md-12">
				<span class="h1"><?php _e('Related posts','inventivo-relatedpostsbytags'); ?></span>
				</div>
				<?php
				while ( $related_posts->have_posts() ) {
					$related_posts->the_post();
					$title = get_the_title();
					$permalink = get_the_permalink();
					$image = get_the_post_thumbnail( get_the_id(), 'middle', array('alt' => $title, 'title' => $title) );
					?>
					<div class="col-md-4">
						<div class="meta">
							<?php
							/*$post_project = wp_get_object_terms($related_posts->post->ID, 'projects');
							$project = 'Pew Research Center';
							$project_slug = '';
							if( isset($post_project[0]) ) {
								$project = $post_project[0]->name;
								$project_slug =  $post_project[0]->slug;
							} elseif( $related_posts->post->post_type == 'fact-tank' ) {
								$project = 'Fact Tank';
								$project_slug = 'fact-tank';
							}
							?>
							<span class="project <?php $project_slug;?> right-seperator"><?php $project;?></span>
							<span class="date"><?php the_time('M j, Y'); ?></span>*/ ?>
						</div>
						<a class="related-post equal" href="<?php echo $permalink; ?>">
							<?php echo $image; ?>
							<span class="h2"><?php echo $title ?></span>
						</a>
					</div>
				<?php }
				wp_reset_postdata();
				?>
				<div class="clear"></div>
			</div>
		<?php
			return ob_get_clean();
		}
	}

	public function register_css()
    {
		wp_register_style('inventivo-related-posts', plugins_url( 'public/css/relatedpostsbytags.css', __FILE__ ));
	}

	public function print_css()
    {
		global $load_css;
		// CSS nur laden, wenn shortcode vorhanden ist
		if (!$load_css) {
			return;
		}
		wp_print_styles('inventivo-related-posts');
	}

	public function register_js()
    {

		wp_enqueue_script( 'inventivo-match-height', plugins_url( 'public/js/jquery.matchHeight.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'inventivo-related-posts', plugins_url( 'public/js/relatedpostsbytags.js', __FILE__ ), array( 'jquery' ) );
	}

	public function load_textdomain()
    {
		load_plugin_textdomain( 'inventivo-relatedpostsbytags', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
	}

	public function my_i18n_debug()
    {

		$loaded=load_plugin_textdomain( 'inventivo-relatedpostsbytags', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

		if ( ! $loaded ){
			echo "<hr/>";
			echo "Error: the mo file was not found! ";
			exit();
		}else{
			echo "<hr/><strong>Debug info</strong>:<br/>";
			//echo "WPLANG: ". WPLANG;
			echo "<br/>";
			echo "translate test: ". __('Related posts by tags | inventivo','relatedposts');
			exit();
		}
	}
}

if ( !is_admin() ) {
	$inventivo_related_posts = new InventivoRelatedPosts();
}