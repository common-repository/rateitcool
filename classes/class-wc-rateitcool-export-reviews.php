<?php

class Rateitcool_Review_Export
{
    const ENCLOSURE = '"';
    const DELIMITER = ',';

    public function downloadReviewToBrowser($file) {
    	$file_absoulute_path = plugin_dir_path( __FILE__ ).'..'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$file;
    	try
    	{
    		if (file_exists($file_absoulute_path)) {
			    header('Content-Description: File Transfer');
			    header('Content-Type: application/octet-stream');
			    header('Content-Disposition: attachment; filename='.($file));
			    header('Content-Transfer-Encoding: binary');
			    header('Expires: 0');
			    header('Cache-Control: must-revalidate');
			    header('Pragma: public');
			    header('Content-Length: ' . filesize($file_absoulute_path));
			    ob_clean();
			    flush();
			    readfile($file_absoulute_path);
			    //delete the file after it was downloaded.
			    unlink($file_absoulute_path);
			    return null;
			}
    	}
        catch (Exception $e)
        {
        	error_log($e->getMessage());
        	return $e->getMessage();
        }
    }

    /**
     * export given reviews to csv file in var/export.
     */
    public function exportReviews()
    {
        try
        {
            $fileName = 'review_export_rateit_cool_'.date("Ymd_His").'.csv';
            $fp = fopen(plugin_dir_path( __FILE__ ).'../tmp/'.$fileName, 'w');
            if ($fp != NULL) {
              $this->writeHeadRow($fp);

              # Load all reviews with thier votes
              $allReviews = $this->getAllReviews();

              foreach ($allReviews as $fullReview)
              {
  	            $this->writeReview($fullReview, $fp);
              }
              fclose($fp);
            }
            return array($fileName, null);
        }
        catch (Exception $e)
        {
        	error_log($e->getMessage());
        	return array(null, $e->getMessage());
        }
    }

    /**
	 * Writes the head row with the column names in the csv file.
	 */
    protected function writeHeadRow($fp)
    {
        fputcsv($fp, $this->getHeadRowValues(), self::DELIMITER, self::ENCLOSURE);
    }

    /**
	 * Writes the row(s) for the given review in the csv file.
	 * A row is added to the csv file for each reviewed item.
	 */
    protected function writeReview($review, $fp)
    {
    	$review = (array) $review;
        fputcsv($fp, $review, self::DELIMITER, self::ENCLOSURE);
    }

    //@todo change the order of fields and add ean field
    protected function getHeadRowValues()
    {
      return array(
        'gpntype',
        'gpnvalue',
      	'title',
        'content',
        'language',
      	'stars',
        'positive',
        'negative',
        'recommend',
        'status',
        'time',
        'category',
        'detail1',
        'detail2',
        'detail3',
        'detail4'
      );
    }


    protected function getAllReviews() {
    	global $wpdb;
      $settings = get_option('rateitcool_settings', wc_rateitcool_get_default_settings());
		  $query = "SELECT
        `".$wpdb->prefix."comments`.`comment_content` as 'content',
        `".$wpdb->prefix."comments`.`comment_date` as 'time',
        `".$wpdb->prefix."commentmeta`.`meta_value` as 'stars',
        `gtinMeta`.`meta_key` as 'gpntype',
        `gtinMeta`.`meta_value` as 'gpnvalue',
        `skuMeta`.`meta_value` as 'sku'

        FROM `".$wpdb->prefix."comments`
        INNER JOIN `".$wpdb->prefix."posts` ON `".$wpdb->prefix."posts`.`ID` = `".$wpdb->prefix."comments`.`comment_post_ID`
        INNER JOIN `".$wpdb->prefix."commentmeta` ON `".$wpdb->prefix."commentmeta`.`comment_id` = `".$wpdb->prefix."comments`.`comment_ID`
        LEFT JOIN `".$wpdb->prefix."postmeta` as `gtinMeta` ON `".$wpdb->prefix."posts`.`ID` = `gtinMeta`.`post_id` AND `gtinMeta`.`meta_key` = '" . $settings['rateitcool_gpntype'] . "'
        INNER JOIN `".$wpdb->prefix."postmeta`as `skuMeta` ON `".$wpdb->prefix."posts`.`ID` = `skuMeta`.`post_id` AND `skuMeta`.`meta_key` = '_sku'
        WHERE `post_type` = 'product' AND `".$wpdb->prefix."commentmeta`.`meta_key`='rating'";

  		$results = $wpdb->get_results($query);
  		$all_reviews = array();
  		foreach ($results as $value) {

  			$current_review = array();
  			$review_content = $this->cleanContent($value->content);

        $current_review['gpntype'] = ($value->gpntype !== NULL?$value->gpntype:$settings['username']);
        $current_review['gpnvalue'] = ($value->gpnvalue !== NULL?$value->gpnvalue:$value->sku);
        $current_review['title'] = $this->getFirstWords($review_content);
        $current_review['content'] = $review_content;
        $current_review['language'] = $settings['language_code'];
        $current_review['stars'] = $value->stars;
        $current_review['positive'] = 0;
        $current_review['negative'] = 0;
        $current_review['recommend'] = 0;
        $current_review['status'] = 1;
  			$all_reviews[] = $current_review;
  		}
  		return $all_reviews;
    }

    private function cleanContent($content) {
    	$content = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $content);
    	return html_entity_decode(strip_tags(strip_shortcodes($content)));
    }

    private function getFirstWords($content = '', $number_of_words = 5) {
    	$words = str_word_count($content,1);
    	if(count($words) > $number_of_words) {
    		return join(" ",array_slice($words, 0, $number_of_words));
    	}
    	else {
    		return join(" ",$words);
    	}
    }
}
?>
