<?php get_header(); ?>

	<main role="main" aria-label="Content">
		<!-- .page-header -->
		<header class="page-header">
		    <?php the_archive_title( '<h1 class="page-title">', '</h1>' );
		        the_archive_description( '<div class="taxonomy-description">', '</div>' ); ?>
		</header>
		<!-- .page-header -->
		<!-- section -->
		<section>

			<h1><?php echo sprintf( __( '%s Search Results for ', 'Creativity Core' ), $wp_query->found_posts ); echo get_search_query(); ?></h1>

			<?php get_template_part( 'loop' ); ?>

			<?php get_template_part( 'pagination' ); ?>

		</section>
		<!-- /section -->
	</main>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
