<?php

namespace BrugOpen\Db\Service;

use BrugOpen\Core\ServiceFactory;
use BrugOpen\Db\Service\DatabaseTableManager;

class DatabaseTableManagerFactory implements ServiceFactory
{

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Core\ServiceFactory::createService()
     */
    public function createService($serviceName, $context)
    {
        $service = null;

        if ($serviceName == 'BrugOpen.TableManager') {

            $connection = $context->getDatabaseConnectionManager()->getConnection();

            $databaseTableManager = new DatabaseTableManager($connection);

            $tableColumnConfigurations = $this->loadTableColumnConfigurations();

            if ($tableColumnConfigurations) {

                foreach (array_keys($tableColumnConfigurations) as $tableName) {

                    $columnDefinitions = $tableColumnConfigurations[$tableName];

                    $databaseTableManager->setColumnDefinitions($tableName, $columnDefinitions);
                }
            }

            $service = $databaseTableManager;
        }

        return $service;
    }

    public function loadTableColumnConfigurations()
    {
        $config = array();

        $config['bo_approach']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_approach']['mmsi'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_approach']['active'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_approach']['bridge_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_approach']['side'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_approach']['first_location'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_approach']['first_speed'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_approach']['first_heading'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_approach']['first_timestamp'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_approach']['last_location'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_approach']['last_speed'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_approach']['last_heading'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_approach']['last_timestamp'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge']['name'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['title'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['distinctive_title'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['province'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['province2'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['city'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['city2'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['operator'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['twitter_title'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['twitter_account_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge']['tweets_enabled'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge']['ndw_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge']['ndw_lat'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['ndw_lng'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['isrs_code'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['connected_segments'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['wiki_url'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['wiki_lat'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['wiki_lng'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['project_operations'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge']['approach1'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['approach2'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge']['announce_approach'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge']['active'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge']['last_started_operation_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge']['num_projections'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge']['num_accurate_projections'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge']['num_accurate_first_projections'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge']['avg_time_until_operation'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge_approach']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_approach']['bridge_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_approach']['mmsi'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_approach']['active'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_approach']['operation_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge_approach']['entry_side'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_approach']['entry_first_timestamp'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_approach']['entry_first_location_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_approach']['entry_last_timestamp'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_approach']['entry_last_location_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_approach']['eta_pass_timestamp'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge_approach']['exit_location_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge_approach']['actual_pass_timestamp'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge_approach']['vessel_name'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge_approach']['vessel_callsign'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge_approach']['vessel_dimensions'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge_approach']['vessel_type'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge_approach']['voyage_destination'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_bridge_approach']['voyage_eta'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge_isrs']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_isrs']['bridge_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_isrs']['isrs_code'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_nearby']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_nearby']['bridge_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_nearby']['nearby_bridge_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_nearby']['distance'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_passage']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_passage']['mmsi'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_passage']['bridge_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_passage']['datetime_passage'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_bridge_passage']['direction'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge_passage']['vessel_type'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_bridge_passage']['operation_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_city']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_city']['urlpart'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_city']['title'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_city']['province_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_event']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_event']['type_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_event']['event_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_event']['datetime_generated'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_mobilitysensing_location']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_mobilitysensing_location']['name'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_mobilitysensing_location']['isrs'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location']['loc_nr'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location']['loc_type'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location']['loc_desc'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location']['road_number'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location']['road_name'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location']['first_name'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location']['second_name'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location_2015']['loc_nr'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location_2015']['loc_type'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location_2015']['loc_desc'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location_2015']['road_number'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location_2015']['road_name'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location_2015']['first_name'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_ndw_location_2015']['second_name'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operating_schedule']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operating_schedule']['date_start'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operating_schedule']['date_end'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operating_schedule']['day_type'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_operating_schedule']['traffic_type'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_operating_schedule']['operating_from'] = DatabaseTableManager::COLUMN_TIME;
        $config['bo_operating_schedule']['operating_until'] = DatabaseTableManager::COLUMN_TIME;
        $config['bo_operation']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operation']['event_id'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operation']['bridge_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_operation']['certainty'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_operation']['certain'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_operation']['datetime_start'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_operation']['time_start'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_operation']['datetime_end'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_operation']['time_end'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_operation']['datetime_gone'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_operation']['time_gone'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_operation']['finished'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operation']['twitter_status_start'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_operation']['twitter_status_end'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_operation']['push_sent_start'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_operation']['push_sent_end'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_operation_projection']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operation_projection']['event_id'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operation_projection']['version'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operation_projection']['operation_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_operation_projection']['bridge_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operation_projection']['certainty'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_operation_projection']['time_start'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_operation_projection']['time_end'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_operation_projection']['datetime_projection'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_passage_projection']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_passage_projection']['journey_id'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_passage_projection']['bridge_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_passage_projection']['datetime_passage'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_passage_projection']['standard_deviation'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_passage_projection']['operation_probability'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_passage_projection']['datetime_projection'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_plaats']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_plaats']['bag_id'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_plaats']['urlpart'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_plaats']['naam'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_plaats']['provincie'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_plaats']['geometrie'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_plaats']['lat_min'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_plaats']['lat_max'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_plaats']['lon_min'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_plaats']['lon_max'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_province']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_province']['code'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_province']['urlpart'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_province']['title'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_message']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_message']['subscription_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_message']['operation_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_message']['payload'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_message']['result'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_message']['datetime_sent'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_message']['response_code'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_push_message']['datetime_created'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_message']['datetime_modified'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_subscription']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_subscription']['guid'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_subscription']['endpoint'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_push_subscription']['expiration_time'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_push_subscription']['auth_publickey'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_push_subscription']['auth_token'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_push_subscription']['content_encoding'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_push_subscription']['datetime_created'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_subscription']['datetime_modified'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_subscription_schedule']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_subscription_schedule']['subscription_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_subscription_schedule']['bridge_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_subscription_schedule']['day'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_subscription_schedule']['time_start'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_push_subscription_schedule']['time_end'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_situation']['id'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_situation']['version'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_situation']['operation_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_situation']['location'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_situation']['lat'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_situation']['lng'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_situation']['datetime_start'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_situation']['time_start'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_situation']['datetime_end'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_situation']['time_end'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_situation']['status'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_situation']['probability'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_situation']['datetime_version'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_situation']['version_time'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_situation']['first_publication'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_situation']['first_publication_time'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_situation']['last_publication'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_situation']['last_publication_time'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_tweet']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_tweet']['account_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_tweet']['twitter_id'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_tweet']['status'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_tweet']['lat'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_tweet']['lng'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_tweet']['datetime_tweet'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_tweet']['bridge_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_tweet']['operation_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_tweet']['deleted'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_tweet']['datetime_created'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_tweet']['datetime_modified'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_twitter_account']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_twitter_account']['username'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_twitter_account']['oauth_access_token'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_twitter_account']['oauth_access_token_secret'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_twitter_account']['consumer_key'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_twitter_account']['consumer_secret'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_vessel_journey']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_journey']['mmsi'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_journey']['first_timestamp'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_vessel_journey']['first_location'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_journey']['last_timestamp'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_vessel_journey']['last_location'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_vessel_journey']['vessel_name'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_vessel_journey']['vessel_callsign'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_vessel_journey']['vessel_dimensions'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_vessel_journey']['vessel_type'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_vessel_journey']['voyage_destination'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_vessel_journey']['voyage_eta'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_vessel_location']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_location']['mmsi'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_location']['timestamp'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_location']['lat'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_location']['lon'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_location']['speed'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_vessel_location']['heading'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_vessel_segment']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_segment']['mmsi'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_segment']['segment_id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_segment']['journey_id'] = DatabaseTableManager::COLUMN_INT;
        $config['bo_vessel_segment']['first_timestamp'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_vessel_segment']['first_location'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_segment']['last_timestamp'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;
        $config['bo_vessel_segment']['last_location'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_vessel_type']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_vessel_type']['title'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_vessel_type']['classification'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_waterway_segment']['id'] = DatabaseTableManager::COLUMN_INT + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_waterway_segment']['title'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['bo_waterway_segment']['coordinates'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_waterway_segment']['connected_segments'] = DatabaseTableManager::COLUMN_STR;
        $config['bo_waterway_segment']['route_points'] = DatabaseTableManager::COLUMN_STR;
        $config['kv_table']['key'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;
        $config['kv_table']['value'] = DatabaseTableManager::COLUMN_STR + DatabaseTableManager::COLUMN_NOTNULL;

        return $config;
    }
}
