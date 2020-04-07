<?php mesmerize_get_header(); ?>
<div class="content post-page">
	<div class="gridContainer">
		<div class="row">
			<div class="col-xs-12 <?php mesmerize_posts_wrapper_class(); ?>">
				<div class="">

					<table id="joblist" class="display" data-page-length="25" data-order='[[ 2, "desc" ]]'>
						<thead>
							<tr role="row">
								<th class="sorting" tabindex="0" aria-controls="joblist" rowspan="1" colspan="1" aria-label="Title: activate to sort column ascending" style="width: 603px;">Title</th>
								<th class="sorting" tabindex="0" aria-controls="joblist" rowspan="1" colspan="1" aria-label="Location: activate to sort column ascending" style="width: 96px;">Location</th>
								<th class="sorting" tabindex="0" aria-controls="joblist" rowspan="1" colspan="1" aria-label="Job ID: activate to sort column ascending" style="width: 326px;">Job ID</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$query = new WP_Query(array('post_type' => 'bullpen-jobs'));
							if (!$query->have_posts()) {
								echo '<div class="no-search-results-message">Your search found no jobs within our database. Try broadening your search or contact us to discuss your job requirements.</div>';
							} else {
								while ($query->have_posts()) {
									$query->the_post();
									$post_id = $query->post->ID;
							?>
									<tr class="clickable-row" data-href="<?php echo the_permalink(); ?>" role="row">
										<td><a href="<?php echo the_permalink(); ?>"><?php echo the_title(); ?></a></td>
										<?php
										$locations = get_the_terms($post_id, "bullpen-locations");
										$location = $locations[0];
										$loc_name = $location->name;
										?>
										<td nowrap="nowrap"><?php echo $loc_name; ?></td>
										<td>
											<?php echo get_post_meta($post_id, "bullhorn_job_id", true); ?>
											<div style="display:none;"><?php echo the_content(); ?></div>
										</td>
									</tr>
							<?php
								} // end while
							} // end if have posts
							?>
						</tbody>
					</table>

				</div>
			</div>
			<?php get_sidebar(); ?>
		</div>
	</div>
</div>

<script>
	jQuery(document).ready(function() {
		jQuery('#joblist').DataTable();
	});
</script>

<?php get_footer(); ?>