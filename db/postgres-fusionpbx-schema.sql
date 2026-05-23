--
-- PostgreSQL database dump
--

\restrict YggB0urmHh9PskZ2naG1eMOWcVuwogrcgPszqoupeogc9sEy8jZpOvDJpPsHpUF

-- Dumped from database version 13.23
-- Dumped by pg_dump version 13.23

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: v_access_control_nodes; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_access_control_nodes (
    access_control_node_uuid uuid NOT NULL,
    access_control_uuid uuid,
    node_type text,
    node_cidr text,
    node_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_access_control_nodes OWNER TO fusionpbx;

--
-- Name: v_access_controls; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_access_controls (
    access_control_uuid uuid NOT NULL,
    access_control_name text,
    access_control_default text,
    access_control_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_access_controls OWNER TO fusionpbx;

--
-- Name: v_bridges; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_bridges (
    bridge_uuid uuid NOT NULL,
    domain_uuid uuid,
    bridge_name text,
    bridge_destination text,
    bridge_enabled boolean,
    bridge_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_bridges OWNER TO fusionpbx;

--
-- Name: v_call_block; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_call_block (
    domain_uuid uuid,
    call_block_uuid uuid NOT NULL,
    call_block_direction text,
    extension_uuid uuid,
    call_block_name text,
    call_block_country_code numeric,
    call_block_number text,
    call_block_count numeric,
    call_block_action text,
    call_block_app text,
    call_block_data text,
    date_added text,
    call_block_enabled boolean,
    call_block_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_call_block OWNER TO fusionpbx;

--
-- Name: v_call_broadcasts; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_call_broadcasts (
    call_broadcast_uuid uuid NOT NULL,
    domain_uuid uuid,
    broadcast_name text,
    broadcast_description text,
    broadcast_start_time numeric,
    broadcast_timeout numeric,
    broadcast_concurrent_limit numeric,
    recording_uuid uuid,
    broadcast_caller_id_name text,
    broadcast_caller_id_number text,
    broadcast_destination_type text,
    broadcast_phone_numbers text,
    broadcast_avmd boolean,
    broadcast_destination_data text,
    broadcast_accountcode text,
    broadcast_toll_allow text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_call_broadcasts OWNER TO fusionpbx;

--
-- Name: v_call_center_agents; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_call_center_agents (
    call_center_agent_uuid uuid NOT NULL,
    domain_uuid uuid,
    user_uuid uuid,
    agent_name text,
    agent_type text,
    agent_call_timeout numeric,
    agent_id text,
    agent_password text,
    agent_contact text,
    agent_status text,
    agent_logout text,
    agent_max_no_answer numeric,
    agent_wrap_up_time numeric,
    agent_reject_delay_time numeric,
    agent_busy_delay_time numeric,
    agent_no_answer_delay_time text,
    agent_record boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_call_center_agents OWNER TO fusionpbx;

--
-- Name: v_call_center_queues; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_call_center_queues (
    call_center_queue_uuid uuid NOT NULL,
    domain_uuid uuid,
    dialplan_uuid uuid,
    queue_name text,
    queue_extension text,
    queue_greeting text,
    queue_strategy text,
    queue_moh_sound text,
    queue_record_template text,
    queue_language text,
    queue_dialect text,
    queue_voice text,
    queue_limit text,
    queue_time_base_score text,
    queue_time_base_score_sec numeric,
    queue_max_wait_time numeric,
    queue_max_wait_time_with_no_agent numeric,
    queue_max_wait_time_with_no_agent_time_reached numeric,
    queue_tier_rules_apply boolean,
    queue_tier_rule_wait_second numeric,
    queue_tier_rule_no_agent_no_wait boolean,
    queue_timeout_action text,
    queue_discard_abandoned_after numeric,
    queue_abandoned_resume_allowed boolean,
    queue_tier_rule_wait_multiply_level boolean,
    queue_cid_prefix text,
    queue_outbound_caller_id_name text,
    queue_outbound_caller_id_number text,
    queue_announce_position text,
    queue_announce_sound text,
    queue_announce_frequency numeric,
    queue_cc_exit_keys text,
    queue_email_address text,
    queue_context text,
    queue_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_call_center_queues OWNER TO fusionpbx;

--
-- Name: v_call_center_tiers; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_call_center_tiers (
    call_center_tier_uuid uuid NOT NULL,
    domain_uuid uuid,
    call_center_queue_uuid uuid,
    call_center_agent_uuid uuid,
    agent_name text,
    queue_name text,
    tier_level numeric,
    tier_position numeric,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_call_center_tiers OWNER TO fusionpbx;

--
-- Name: v_call_flows; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_call_flows (
    domain_uuid uuid,
    call_flow_uuid uuid NOT NULL,
    dialplan_uuid uuid,
    call_flow_name text,
    call_flow_extension text,
    call_flow_feature_code text,
    call_flow_context text,
    call_flow_status text,
    call_flow_pin_number text,
    call_flow_label text,
    call_flow_sound text,
    call_flow_app text,
    call_flow_data text,
    call_flow_alternate_label text,
    call_flow_alternate_sound text,
    call_flow_alternate_app text,
    call_flow_alternate_data text,
    call_flow_enabled boolean,
    call_flow_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_call_flows OWNER TO fusionpbx;

--
-- Name: v_conference_centers; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conference_centers (
    domain_uuid uuid,
    conference_center_uuid uuid NOT NULL,
    dialplan_uuid uuid,
    conference_center_name text,
    conference_center_extension text,
    conference_center_pin_length numeric,
    conference_center_greeting text,
    conference_center_description text,
    conference_center_enabled boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conference_centers OWNER TO fusionpbx;

--
-- Name: v_conference_control_details; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conference_control_details (
    conference_control_detail_uuid uuid NOT NULL,
    conference_control_uuid uuid,
    control_digits text,
    control_action text,
    control_data text,
    control_enabled boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conference_control_details OWNER TO fusionpbx;

--
-- Name: v_conference_controls; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conference_controls (
    conference_control_uuid uuid NOT NULL,
    control_name text,
    control_enabled boolean,
    control_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conference_controls OWNER TO fusionpbx;

--
-- Name: v_conference_profile_params; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conference_profile_params (
    conference_profile_param_uuid uuid NOT NULL,
    conference_profile_uuid uuid,
    profile_param_name text,
    profile_param_value text,
    profile_param_enabled boolean,
    profile_param_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conference_profile_params OWNER TO fusionpbx;

--
-- Name: v_conference_profiles; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conference_profiles (
    conference_profile_uuid uuid NOT NULL,
    profile_name text,
    profile_enabled boolean,
    profile_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conference_profiles OWNER TO fusionpbx;

--
-- Name: v_conference_room_users; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conference_room_users (
    domain_uuid uuid,
    conference_room_user_uuid uuid NOT NULL,
    conference_room_uuid uuid,
    user_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conference_room_users OWNER TO fusionpbx;

--
-- Name: v_conference_rooms; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conference_rooms (
    domain_uuid uuid,
    conference_room_uuid uuid NOT NULL,
    conference_center_uuid uuid,
    conference_room_name text,
    profile text,
    record boolean,
    moderator_pin text,
    participant_pin text,
    max_members numeric,
    start_datetime text,
    stop_datetime text,
    wait_mod boolean,
    moderator_endconf boolean,
    announce_name boolean,
    announce_count boolean,
    announce_recording boolean,
    sounds boolean,
    mute boolean,
    created text,
    created_by text,
    email_address text,
    account_code text,
    enabled boolean,
    description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conference_rooms OWNER TO fusionpbx;

--
-- Name: v_conference_session_details; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conference_session_details (
    domain_uuid uuid,
    conference_session_detail_uuid uuid NOT NULL,
    conference_session_uuid uuid,
    meeting_uuid uuid,
    username text,
    caller_id_name text,
    caller_id_number text,
    uuid uuid,
    moderator text,
    network_addr text,
    start_epoch numeric,
    end_epoch numeric,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conference_session_details OWNER TO fusionpbx;

--
-- Name: v_conference_sessions; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conference_sessions (
    domain_uuid uuid,
    conference_session_uuid uuid NOT NULL,
    meeting_uuid uuid,
    profile text,
    recording text,
    start_epoch numeric,
    end_epoch numeric,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conference_sessions OWNER TO fusionpbx;

--
-- Name: v_conference_users; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conference_users (
    conference_user_uuid uuid NOT NULL,
    domain_uuid uuid,
    conference_uuid uuid,
    user_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conference_users OWNER TO fusionpbx;

--
-- Name: v_conferences; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_conferences (
    domain_uuid uuid,
    conference_uuid uuid NOT NULL,
    dialplan_uuid uuid,
    conference_name text,
    conference_extension text,
    conference_pin_number text,
    conference_profile text,
    conference_email_address text,
    conference_account_code text,
    conference_flags text,
    conference_order numeric,
    conference_description text,
    conference_context text,
    conference_enabled boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_conferences OWNER TO fusionpbx;

--
-- Name: v_contact_addresses; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_addresses (
    contact_address_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_uuid uuid,
    address_type text,
    address_label text,
    address_primary boolean,
    address_street text,
    address_extended text,
    address_community text,
    address_locality text,
    address_region text,
    address_postal_code text,
    address_country text,
    address_latitude text,
    address_longitude text,
    address_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_addresses OWNER TO fusionpbx;

--
-- Name: v_contact_attachments; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_attachments (
    contact_attachment_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_uuid uuid,
    attachment_primary boolean,
    attachment_filename text,
    attachment_content text,
    attachment_description text,
    attachment_uploaded_date timestamp with time zone,
    attachment_uploaded_user_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_attachments OWNER TO fusionpbx;

--
-- Name: v_contact_emails; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_emails (
    contact_email_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_uuid uuid,
    email_label text,
    email_primary boolean,
    email_address text,
    email_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_emails OWNER TO fusionpbx;

--
-- Name: v_contact_groups; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_groups (
    contact_group_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_uuid uuid,
    group_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_groups OWNER TO fusionpbx;

--
-- Name: v_contact_notes; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_notes (
    contact_note_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_uuid uuid,
    contact_note text,
    last_mod_date text,
    last_mod_user text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_notes OWNER TO fusionpbx;

--
-- Name: v_contact_phones; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_phones (
    contact_phone_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_uuid uuid,
    phone_label text,
    phone_type_voice numeric,
    phone_type_fax numeric,
    phone_type_video numeric,
    phone_type_text numeric,
    phone_speed_dial text,
    phone_country_code numeric,
    phone_number text,
    phone_extension text,
    phone_primary boolean,
    phone_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_phones OWNER TO fusionpbx;

--
-- Name: v_contact_relations; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_relations (
    contact_relation_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_uuid uuid,
    relation_label text,
    relation_contact_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_relations OWNER TO fusionpbx;

--
-- Name: v_contact_settings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_settings (
    contact_setting_uuid uuid NOT NULL,
    contact_uuid uuid,
    domain_uuid uuid,
    contact_setting_category text,
    contact_setting_subcategory text,
    contact_setting_name text,
    contact_setting_value text,
    contact_setting_order numeric,
    contact_setting_enabled boolean,
    contact_setting_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_settings OWNER TO fusionpbx;

--
-- Name: v_contact_times; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_times (
    domain_uuid uuid,
    contact_time_uuid uuid NOT NULL,
    contact_uuid uuid,
    user_uuid uuid,
    time_start timestamp with time zone,
    time_stop timestamp with time zone,
    time_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_times OWNER TO fusionpbx;

--
-- Name: v_contact_urls; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_urls (
    contact_url_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_uuid uuid,
    url_type text,
    url_label text,
    url_primary boolean,
    url_address text,
    url_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_urls OWNER TO fusionpbx;

--
-- Name: v_contact_users; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contact_users (
    contact_user_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_uuid uuid,
    user_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contact_users OWNER TO fusionpbx;

--
-- Name: v_contacts; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_contacts (
    contact_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_parent_uuid uuid,
    contact_type text,
    contact_organization text,
    contact_name_prefix text,
    contact_name_given text,
    contact_name_middle text,
    contact_name_family text,
    contact_name_suffix text,
    contact_nickname text,
    contact_title text,
    contact_role text,
    contact_category text,
    contact_url text,
    contact_time_zone text,
    contact_note text,
    last_mod_date text,
    last_mod_user text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_contacts OWNER TO fusionpbx;

--
-- Name: v_countries; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_countries (
    country_uuid uuid NOT NULL,
    country text,
    iso_a2 text,
    iso_a3 text,
    num numeric,
    country_code text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_countries OWNER TO fusionpbx;

--
-- Name: v_dashboard_widget_groups; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_dashboard_widget_groups (
    dashboard_uuid uuid,
    dashboard_widget_group_uuid uuid NOT NULL,
    dashboard_widget_uuid uuid,
    group_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_dashboard_widget_groups OWNER TO fusionpbx;

--
-- Name: v_dashboard_widgets; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_dashboard_widgets (
    dashboard_uuid uuid,
    dashboard_widget_uuid uuid NOT NULL,
    dashboard_widget_parent_uuid uuid,
    widget_name text,
    widget_path text,
    widget_icon text,
    widget_icon_color text,
    widget_url text,
    widget_target text,
    widget_width numeric,
    widget_height numeric,
    widget_content text,
    widget_content_text_align text,
    widget_content_details text,
    widget_chart_type text,
    widget_label_enabled boolean,
    widget_label_text_color text,
    widget_label_text_color_hover text,
    widget_label_background_color text,
    widget_label_background_color_hover text,
    widget_number_text_color text,
    widget_number_text_color_hover text,
    widget_number_background_color text,
    widget_background_color text,
    widget_background_color_hover text,
    widget_detail_background_color text,
    widget_background_gradient_style text,
    widget_background_gradient_angle text,
    widget_column_span numeric,
    widget_row_span numeric,
    widget_details_state text,
    widget_order numeric,
    widget_enabled boolean,
    widget_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_dashboard_widgets OWNER TO fusionpbx;

--
-- Name: v_dashboards; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_dashboards (
    domain_uuid uuid,
    dashboard_uuid uuid NOT NULL,
    dashboard_name text,
    dashboard_enabled boolean,
    dashboard_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_dashboards OWNER TO fusionpbx;

--
-- Name: v_database_transactions; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_database_transactions (
    domain_uuid uuid,
    database_transaction_uuid uuid NOT NULL,
    user_uuid uuid,
    app_name text,
    app_uuid uuid,
    transaction_code text,
    transaction_address text,
    transaction_type text,
    transaction_date timestamp with time zone,
    transaction_old text,
    transaction_new text,
    transaction_result text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_database_transactions OWNER TO fusionpbx;

--
-- Name: v_databases; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_databases (
    database_uuid uuid NOT NULL,
    database_driver text,
    database_type text,
    database_host text,
    database_port text,
    database_name text,
    database_username text,
    database_password text,
    database_path text,
    database_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_databases OWNER TO fusionpbx;

--
-- Name: v_default_settings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_default_settings (
    default_setting_uuid uuid NOT NULL,
    app_uuid uuid,
    default_setting_category text,
    default_setting_subcategory text,
    default_setting_name text,
    default_setting_value text,
    default_setting_order numeric,
    default_setting_enabled boolean,
    default_setting_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_default_settings OWNER TO fusionpbx;

--
-- Name: v_destinations; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_destinations (
    domain_uuid uuid,
    destination_uuid uuid NOT NULL,
    dialplan_uuid uuid,
    fax_uuid uuid,
    user_uuid uuid,
    group_uuid uuid,
    provider_uuid uuid,
    destination_type text,
    destination_number text,
    destination_trunk_prefix text,
    destination_area_code text,
    destination_prefix text,
    destination_condition_field text,
    destination_number_regex text,
    destination_caller_id_name text,
    destination_caller_id_number text,
    destination_cid_name_prefix text,
    destination_context text,
    destination_record boolean,
    destination_hold_music text,
    destination_distinctive_ring text,
    destination_ringback text,
    destination_accountcode text,
    destination_type_voice numeric,
    destination_type_fax numeric,
    destination_type_emergency numeric,
    destination_type_text numeric,
    destination_conditions json,
    destination_actions json,
    destination_app text,
    destination_data text,
    destination_alternate_app text,
    destination_alternate_data text,
    destination_order numeric,
    destination_enabled boolean,
    destination_description text,
    destination_email boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_destinations OWNER TO fusionpbx;

--
-- Name: v_device_keys; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_device_keys (
    domain_uuid uuid,
    device_key_uuid uuid NOT NULL,
    device_uuid uuid,
    device_key_id numeric,
    device_key_category text,
    device_key_vendor text,
    device_key_type text,
    device_key_subtype text,
    device_key_line numeric,
    device_key_value text,
    device_key_extension text,
    device_key_protected text,
    device_key_label text,
    device_key_icon text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_device_keys OWNER TO fusionpbx;

--
-- Name: v_device_lines; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_device_lines (
    domain_uuid uuid,
    device_line_uuid uuid NOT NULL,
    device_uuid uuid,
    line_number text,
    server_address text,
    server_address_primary text,
    server_address_secondary text,
    outbound_proxy_primary text,
    outbound_proxy_secondary text,
    label text,
    display_name text,
    user_id text,
    auth_id text,
    password text,
    sip_port numeric,
    sip_transport text,
    register_expires numeric,
    shared_line text,
    enabled boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_device_lines OWNER TO fusionpbx;

--
-- Name: v_device_profile_keys; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_device_profile_keys (
    device_profile_key_uuid uuid NOT NULL,
    domain_uuid uuid,
    device_profile_uuid uuid,
    profile_key_id numeric,
    profile_key_category text,
    profile_key_vendor text,
    profile_key_type text,
    profile_key_subtype text,
    profile_key_line numeric,
    profile_key_value text,
    profile_key_extension text,
    profile_key_protected boolean,
    profile_key_label text,
    profile_key_icon text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_device_profile_keys OWNER TO fusionpbx;

--
-- Name: v_device_profile_settings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_device_profile_settings (
    device_profile_setting_uuid uuid NOT NULL,
    domain_uuid uuid,
    device_profile_uuid uuid,
    profile_setting_name text,
    profile_setting_value text,
    profile_setting_enabled boolean,
    profile_setting_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_device_profile_settings OWNER TO fusionpbx;

--
-- Name: v_device_profiles; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_device_profiles (
    device_profile_uuid uuid NOT NULL,
    domain_uuid uuid,
    device_profile_name text,
    device_profile_enabled boolean,
    device_profile_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_device_profiles OWNER TO fusionpbx;

--
-- Name: v_device_settings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_device_settings (
    device_setting_uuid uuid NOT NULL,
    device_uuid uuid,
    domain_uuid uuid,
    device_setting_category text,
    device_setting_subcategory text,
    device_setting_name text,
    device_setting_value text,
    device_setting_enabled boolean,
    device_setting_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_device_settings OWNER TO fusionpbx;

--
-- Name: v_device_vendor_function_groups; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_device_vendor_function_groups (
    device_vendor_function_group_uuid uuid NOT NULL,
    device_vendor_function_uuid uuid,
    device_vendor_uuid uuid,
    group_name text,
    group_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_device_vendor_function_groups OWNER TO fusionpbx;

--
-- Name: v_device_vendor_functions; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_device_vendor_functions (
    device_vendor_function_uuid uuid NOT NULL,
    device_vendor_uuid uuid,
    type text,
    subtype text,
    value text,
    enabled boolean,
    description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_device_vendor_functions OWNER TO fusionpbx;

--
-- Name: v_device_vendors; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_device_vendors (
    device_vendor_uuid uuid NOT NULL,
    name text,
    enabled boolean,
    description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_device_vendors OWNER TO fusionpbx;

--
-- Name: v_devices; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_devices (
    device_uuid uuid NOT NULL,
    domain_uuid uuid,
    device_profile_uuid uuid,
    device_address text,
    device_label text,
    device_vendor text,
    device_location text,
    device_serial_number text,
    device_model text,
    device_firmware_version text,
    device_enabled boolean,
    device_enabled_date timestamp with time zone,
    device_template text,
    device_user_uuid uuid,
    device_username text,
    device_password text,
    device_uuid_alternate uuid,
    device_description text,
    device_provisioned_date timestamp with time zone,
    device_provisioned_method text,
    device_provisioned_ip text,
    device_provisioned_agent text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_devices OWNER TO fusionpbx;

--
-- Name: v_dialplan_details; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_dialplan_details (
    domain_uuid uuid,
    dialplan_uuid uuid,
    dialplan_detail_uuid uuid NOT NULL,
    dialplan_detail_tag text,
    dialplan_detail_type text,
    dialplan_detail_data text,
    dialplan_detail_break text,
    dialplan_detail_inline text,
    dialplan_detail_group numeric,
    dialplan_detail_order numeric,
    dialplan_detail_enabled boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_dialplan_details OWNER TO fusionpbx;

--
-- Name: v_dialplans; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_dialplans (
    domain_uuid uuid,
    dialplan_uuid uuid NOT NULL,
    app_uuid uuid,
    hostname text,
    dialplan_context text,
    dialplan_name text,
    dialplan_number text,
    dialplan_destination boolean,
    dialplan_continue boolean,
    dialplan_xml text,
    dialplan_order numeric,
    dialplan_enabled boolean,
    dialplan_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_dialplans OWNER TO fusionpbx;

--
-- Name: v_domain_settings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_domain_settings (
    domain_uuid uuid,
    domain_setting_uuid uuid NOT NULL,
    app_uuid uuid,
    domain_setting_category text,
    domain_setting_subcategory text,
    domain_setting_name text,
    domain_setting_value text,
    domain_setting_order numeric,
    domain_setting_enabled boolean,
    domain_setting_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_domain_settings OWNER TO fusionpbx;

--
-- Name: v_domains; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_domains (
    domain_uuid uuid NOT NULL,
    domain_parent_uuid uuid,
    domain_name text,
    domain_enabled boolean,
    domain_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_domains OWNER TO fusionpbx;

--
-- Name: v_email_queue; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_email_queue (
    email_queue_uuid uuid NOT NULL,
    domain_uuid uuid,
    hostname text,
    email_date timestamp with time zone,
    email_from text,
    email_to text,
    email_subject text,
    email_body text,
    email_status text,
    email_retry_count numeric,
    email_action_before text,
    email_action_after text,
    email_uuid uuid,
    email_transcription text,
    email_response text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_email_queue OWNER TO fusionpbx;

--
-- Name: v_email_queue_attachments; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_email_queue_attachments (
    email_queue_attachment_uuid uuid NOT NULL,
    domain_uuid uuid,
    email_queue_uuid uuid,
    email_attachment_mime_type text,
    email_attachment_type text,
    email_attachment_path text,
    email_attachment_name text,
    email_attachment_base64 text,
    email_attachment_cid text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_email_queue_attachments OWNER TO fusionpbx;

--
-- Name: v_email_templates; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_email_templates (
    email_template_uuid uuid NOT NULL,
    domain_uuid uuid,
    template_language text,
    template_category text,
    template_subcategory text,
    template_subject text,
    template_body text,
    template_type text,
    template_enabled boolean,
    template_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_email_templates OWNER TO fusionpbx;

--
-- Name: v_emergency_logs; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_emergency_logs (
    emergency_log_uuid uuid NOT NULL,
    domain_uuid uuid,
    extension numeric,
    event text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_emergency_logs OWNER TO fusionpbx;

--
-- Name: v_event_guard_logs; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_event_guard_logs (
    event_guard_log_uuid uuid NOT NULL,
    hostname text,
    log_date timestamp with time zone,
    filter text,
    ip_address text,
    extension text,
    user_agent text,
    log_status text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_event_guard_logs OWNER TO fusionpbx;

--
-- Name: v_extension_settings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_extension_settings (
    extension_setting_uuid uuid NOT NULL,
    domain_uuid uuid,
    extension_uuid uuid,
    extension_setting_type text,
    extension_setting_name text,
    extension_setting_value text,
    extension_setting_enabled boolean,
    extension_setting_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_extension_settings OWNER TO fusionpbx;

--
-- Name: v_extension_users; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_extension_users (
    extension_user_uuid uuid NOT NULL,
    domain_uuid uuid,
    extension_uuid uuid,
    user_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_extension_users OWNER TO fusionpbx;

--
-- Name: v_extensions; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_extensions (
    extension_uuid uuid NOT NULL,
    domain_uuid uuid,
    extension text,
    number_alias text,
    password text,
    accountcode text,
    effective_caller_id_name text,
    effective_caller_id_number text,
    outbound_caller_id_name text,
    outbound_caller_id_number text,
    emergency_caller_id_name text,
    emergency_caller_id_number text,
    directory_first_name text,
    directory_last_name text,
    directory_visible boolean,
    directory_exten_visible boolean,
    max_registrations text,
    limit_max text,
    limit_destination text,
    missed_call_app text,
    missed_call_data text,
    user_context text,
    toll_allow text,
    call_timeout numeric,
    call_group text,
    call_screen_enabled boolean,
    user_record text,
    hold_music text,
    auth_acl text,
    cidr text,
    sip_force_contact text,
    nibble_account text,
    sip_force_expires numeric,
    mwi_account text,
    sip_bypass_media text,
    unique_id numeric,
    dial_string text,
    dial_user text,
    dial_domain text,
    do_not_disturb boolean,
    forward_all_destination text,
    forward_all_enabled boolean,
    forward_busy_destination text,
    forward_busy_enabled boolean,
    forward_no_answer_destination text,
    forward_no_answer_enabled boolean,
    forward_user_not_registered_destination text,
    forward_user_not_registered_enabled boolean,
    follow_me_uuid uuid,
    follow_me_enabled boolean,
    follow_me_destinations text,
    extension_language text,
    extension_dialect text,
    extension_voice text,
    extension_type text,
    enabled boolean,
    description text,
    absolute_codec_string text,
    force_ping boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_extensions OWNER TO fusionpbx;

--
-- Name: v_fax; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_fax (
    fax_uuid uuid NOT NULL,
    domain_uuid uuid,
    dialplan_uuid uuid,
    fax_extension text,
    fax_destination_number text,
    fax_prefix text,
    fax_name text,
    fax_email text,
    fax_email_confirmation text,
    fax_file text,
    fax_email_connection_type text,
    fax_email_connection_host text,
    fax_email_connection_port text,
    fax_email_connection_security text,
    fax_email_connection_validate boolean,
    fax_email_connection_username text,
    fax_email_connection_password text,
    fax_email_connection_mailbox text,
    fax_email_inbound_subject_tag text,
    fax_email_outbound_subject_tag text,
    fax_email_outbound_authorized_senders text,
    fax_pin_number text,
    fax_caller_id_name text,
    fax_caller_id_number text,
    fax_toll_allow text,
    fax_forward_number text,
    fax_send_channels numeric,
    fax_description text,
    accountcode text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_fax OWNER TO fusionpbx;

--
-- Name: v_fax_files; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_fax_files (
    domain_uuid uuid,
    fax_file_uuid uuid NOT NULL,
    fax_uuid uuid,
    fax_mode text,
    fax_recipient text,
    fax_destination text,
    fax_file_type text,
    fax_file_path text,
    fax_caller_id_name text,
    fax_caller_id_number text,
    fax_date timestamp with time zone,
    fax_epoch numeric,
    fax_base64 text,
    read_date timestamp with time zone,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_fax_files OWNER TO fusionpbx;

--
-- Name: v_fax_logs; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_fax_logs (
    fax_log_uuid uuid NOT NULL,
    domain_uuid uuid,
    fax_uuid uuid,
    fax_success text,
    fax_result_code numeric,
    fax_result_text text,
    fax_file text,
    fax_ecm_used text,
    fax_local_station_id text,
    fax_document_transferred_pages numeric,
    fax_document_total_pages numeric,
    fax_image_resolution text,
    fax_image_size numeric,
    fax_bad_rows numeric,
    fax_transfer_rate numeric,
    fax_retry_attempts numeric,
    fax_retry_limit numeric,
    fax_retry_sleep numeric,
    fax_uri text,
    fax_duration numeric,
    fax_date timestamp with time zone,
    fax_epoch numeric,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_fax_logs OWNER TO fusionpbx;

--
-- Name: v_fax_queue; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_fax_queue (
    fax_queue_uuid uuid NOT NULL,
    domain_uuid uuid,
    fax_uuid uuid,
    origination_uuid uuid,
    fax_log_uuid uuid,
    fax_date timestamp with time zone,
    hostname text,
    fax_caller_id_name text,
    fax_caller_id_number text,
    fax_recipient text,
    fax_number text,
    fax_prefix text,
    fax_email_address text,
    fax_file text,
    fax_status text,
    fax_retry_date timestamp with time zone,
    fax_notify_sent boolean,
    fax_notify_date timestamp with time zone,
    fax_retry_count numeric,
    fax_accountcode text,
    fax_command text,
    fax_response text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_fax_queue OWNER TO fusionpbx;

--
-- Name: v_fax_users; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_fax_users (
    fax_user_uuid uuid NOT NULL,
    domain_uuid uuid,
    fax_uuid uuid,
    user_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_fax_users OWNER TO fusionpbx;

--
-- Name: v_fifo; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_fifo (
    fifo_uuid uuid NOT NULL,
    domain_uuid uuid,
    dialplan_uuid uuid,
    fifo_name text,
    fifo_extension text,
    fifo_agent_queue text,
    fifo_agent_status text,
    fifo_strategy text,
    fifo_members text,
    fifo_timeout_seconds text,
    fifo_exit_key numeric,
    fifo_exit_action text,
    fifo_music text,
    fifo_order numeric,
    fifo_enabled boolean,
    fifo_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_fifo OWNER TO fusionpbx;

--
-- Name: v_fifo_members; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_fifo_members (
    fifo_member_uuid uuid NOT NULL,
    domain_uuid uuid,
    fifo_uuid uuid,
    member_contact text,
    member_call_timeout numeric,
    member_simultaneous numeric,
    member_wrap_up_time numeric,
    member_enabled boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_fifo_members OWNER TO fusionpbx;

--
-- Name: v_follow_me; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_follow_me (
    domain_uuid uuid,
    follow_me_uuid uuid NOT NULL,
    cid_name_prefix text,
    cid_number_prefix text,
    dial_string text,
    follow_me_enabled boolean,
    follow_me_ignore_busy boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_follow_me OWNER TO fusionpbx;

--
-- Name: v_follow_me_destinations; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_follow_me_destinations (
    domain_uuid uuid,
    follow_me_uuid uuid,
    follow_me_destination_uuid uuid NOT NULL,
    follow_me_destination text,
    follow_me_delay numeric,
    follow_me_timeout numeric,
    follow_me_prompt text,
    follow_me_order numeric,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_follow_me_destinations OWNER TO fusionpbx;

--
-- Name: v_gateways; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_gateways (
    gateway_uuid uuid NOT NULL,
    domain_uuid uuid,
    gateway text,
    username text,
    password text,
    distinct_to boolean,
    auth_username text,
    realm text,
    from_user text,
    from_domain text,
    proxy text,
    register_proxy text,
    outbound_proxy text,
    expire_seconds numeric,
    register boolean,
    register_transport text,
    contact_params text,
    retry_seconds numeric,
    extension text,
    ping text,
    ping_min text,
    ping_max text,
    contact_in_ping boolean,
    caller_id_in_from boolean,
    supress_cng boolean,
    sip_cid_type text,
    codec_prefs text,
    channels numeric,
    extension_in_contact text,
    context text,
    profile text,
    hostname text,
    enabled boolean,
    description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_gateways OWNER TO fusionpbx;

--
-- Name: v_group_permissions; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_group_permissions (
    group_permission_uuid uuid NOT NULL,
    domain_uuid uuid,
    permission_name text,
    permission_protected text,
    permission_assigned text,
    group_name text,
    group_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_group_permissions OWNER TO fusionpbx;

--
-- Name: v_groups; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_groups (
    group_uuid uuid NOT NULL,
    domain_uuid uuid,
    group_name text,
    group_protected boolean,
    group_level numeric,
    group_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_groups OWNER TO fusionpbx;

--
-- Name: v_ivr_menu_options; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_ivr_menu_options (
    ivr_menu_option_uuid uuid NOT NULL,
    ivr_menu_uuid uuid,
    domain_uuid uuid,
    ivr_menu_option_digits text,
    ivr_menu_option_action text,
    ivr_menu_option_param text,
    ivr_menu_option_order numeric,
    ivr_menu_option_description text,
    ivr_menu_option_enabled boolean,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_ivr_menu_options OWNER TO fusionpbx;

--
-- Name: v_ivr_menus; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_ivr_menus (
    ivr_menu_uuid uuid NOT NULL,
    domain_uuid uuid,
    dialplan_uuid uuid,
    ivr_menu_name text,
    ivr_menu_extension text,
    ivr_menu_parent_uuid uuid,
    ivr_menu_language text,
    ivr_menu_dialect text,
    ivr_menu_voice text,
    ivr_menu_greet_long text,
    ivr_menu_greet_short text,
    ivr_menu_invalid_sound text,
    ivr_menu_exit_sound text,
    ivr_menu_pin_number text,
    ivr_menu_confirm_macro text,
    ivr_menu_confirm_key text,
    ivr_menu_tts_engine text,
    ivr_menu_tts_voice text,
    ivr_menu_confirm_attempts numeric,
    ivr_menu_timeout numeric,
    ivr_menu_exit_app text,
    ivr_menu_exit_data text,
    ivr_menu_inter_digit_timeout numeric,
    ivr_menu_max_failures numeric,
    ivr_menu_max_timeouts numeric,
    ivr_menu_digit_len numeric,
    ivr_menu_direct_dial boolean,
    ivr_menu_ringback text,
    ivr_menu_cid_prefix text,
    ivr_menu_context text,
    ivr_menu_enabled boolean,
    ivr_menu_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_ivr_menus OWNER TO fusionpbx;

--
-- Name: v_languages; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_languages (
    language_uuid uuid NOT NULL,
    language text,
    code text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_languages OWNER TO fusionpbx;

--
-- Name: v_menu_item_groups; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_menu_item_groups (
    menu_item_group_uuid uuid NOT NULL,
    menu_uuid uuid,
    menu_item_uuid uuid,
    group_name text,
    group_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_menu_item_groups OWNER TO fusionpbx;

--
-- Name: v_menu_items; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_menu_items (
    menu_item_uuid uuid NOT NULL,
    menu_uuid uuid,
    menu_item_parent_uuid uuid,
    uuid uuid,
    menu_item_title text,
    menu_item_link text,
    menu_item_icon text,
    menu_item_icon_color text,
    menu_item_category text,
    menu_item_order numeric,
    menu_item_description text,
    menu_item_add_user text,
    menu_item_add_date text,
    menu_item_mod_user text,
    menu_item_mod_date text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_menu_items OWNER TO fusionpbx;

--
-- Name: v_menu_languages; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_menu_languages (
    menu_language_uuid uuid NOT NULL,
    menu_uuid uuid,
    menu_item_uuid uuid,
    menu_language text,
    menu_item_title text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_menu_languages OWNER TO fusionpbx;

--
-- Name: v_menus; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_menus (
    menu_uuid uuid NOT NULL,
    menu_name text,
    menu_language text,
    menu_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_menus OWNER TO fusionpbx;

--
-- Name: v_modules; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_modules (
    module_uuid uuid NOT NULL,
    module_label text,
    module_name text,
    module_category text,
    module_order numeric,
    module_enabled boolean,
    module_default_enabled boolean,
    module_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_modules OWNER TO fusionpbx;

--
-- Name: v_music_on_hold; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_music_on_hold (
    music_on_hold_uuid uuid NOT NULL,
    domain_uuid uuid,
    music_on_hold_name text,
    music_on_hold_path text,
    music_on_hold_rate numeric,
    music_on_hold_shuffle text,
    music_on_hold_channels numeric,
    music_on_hold_interval numeric,
    music_on_hold_timer_name text,
    music_on_hold_chime_list text,
    music_on_hold_chime_freq numeric,
    music_on_hold_chime_max numeric,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_music_on_hold OWNER TO fusionpbx;

--
-- Name: v_notifications; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_notifications (
    notification_uuid uuid NOT NULL,
    project_notifications text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_notifications OWNER TO fusionpbx;

--
-- Name: v_number_translation_details; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_number_translation_details (
    number_translation_detail_uuid uuid NOT NULL,
    number_translation_uuid uuid,
    number_translation_detail_regex text,
    number_translation_detail_replace text,
    number_translation_detail_order text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_number_translation_details OWNER TO fusionpbx;

--
-- Name: v_number_translations; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_number_translations (
    number_translation_uuid uuid NOT NULL,
    number_translation_name text,
    number_translation_enabled boolean,
    number_translation_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_number_translations OWNER TO fusionpbx;

--
-- Name: v_permissions; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_permissions (
    permission_uuid uuid NOT NULL,
    application_uuid uuid,
    application_name text,
    permission_name text,
    permission_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_permissions OWNER TO fusionpbx;

--
-- Name: v_phrase_details; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_phrase_details (
    phrase_detail_uuid uuid NOT NULL,
    phrase_uuid uuid,
    domain_uuid uuid,
    phrase_detail_group numeric,
    phrase_detail_tag text,
    phrase_detail_pattern text,
    phrase_detail_function text,
    phrase_detail_data text,
    phrase_detail_method text,
    phrase_detail_type text,
    phrase_detail_order text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_phrase_details OWNER TO fusionpbx;

--
-- Name: v_phrases; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_phrases (
    phrase_uuid uuid NOT NULL,
    domain_uuid uuid,
    phrase_name text,
    phrase_language text,
    phrase_enabled boolean,
    phrase_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_phrases OWNER TO fusionpbx;

--
-- Name: v_pin_numbers; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_pin_numbers (
    domain_uuid uuid,
    pin_number_uuid uuid NOT NULL,
    pin_number text,
    accountcode text,
    enabled boolean,
    description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_pin_numbers OWNER TO fusionpbx;

--
-- Name: v_recordings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_recordings (
    recording_uuid uuid NOT NULL,
    domain_uuid uuid,
    recording_filename text,
    recording_name text,
    recording_voice text,
    recording_speed numeric,
    recording_message text,
    recording_description text,
    recording_base64 text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_recordings OWNER TO fusionpbx;

--
-- Name: v_ring_group_destinations; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_ring_group_destinations (
    ring_group_destination_uuid uuid NOT NULL,
    domain_uuid uuid,
    ring_group_uuid uuid,
    destination_number text,
    destination_delay numeric,
    destination_timeout numeric,
    destination_enabled boolean,
    destination_prompt numeric,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid,
    destination_description text
);


ALTER TABLE public.v_ring_group_destinations OWNER TO fusionpbx;

--
-- Name: v_ring_group_users; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_ring_group_users (
    ring_group_user_uuid uuid NOT NULL,
    domain_uuid uuid,
    ring_group_uuid uuid,
    user_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_ring_group_users OWNER TO fusionpbx;

--
-- Name: v_ring_groups; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_ring_groups (
    domain_uuid uuid,
    ring_group_uuid uuid NOT NULL,
    ring_group_name text,
    ring_group_extension text,
    ring_group_greeting text,
    ring_group_exit_key text,
    ring_group_call_timeout numeric,
    ring_group_forward_destination text,
    ring_group_forward_enabled boolean,
    ring_group_caller_id_name text,
    ring_group_caller_id_number text,
    ring_group_cid_name_prefix text,
    ring_group_cid_number_prefix text,
    ring_group_strategy text,
    ring_group_timeout_app text,
    ring_group_timeout_data text,
    ring_group_distinctive_ring text,
    ring_group_ringback text,
    ring_group_call_screen_enabled boolean,
    ring_group_call_forward_enabled boolean,
    ring_group_follow_me_enabled boolean,
    ring_group_missed_call_app text,
    ring_group_missed_call_data text,
    ring_group_context text,
    ring_group_enabled boolean,
    ring_group_description text,
    dialplan_uuid uuid,
    ring_group_forward_toll_allow text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_ring_groups OWNER TO fusionpbx;

--
-- Name: v_sip_profile_domains; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_sip_profile_domains (
    sip_profile_domain_uuid uuid NOT NULL,
    sip_profile_uuid uuid,
    sip_profile_domain_name text,
    sip_profile_domain_alias text,
    sip_profile_domain_parse text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_sip_profile_domains OWNER TO fusionpbx;

--
-- Name: v_sip_profile_settings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_sip_profile_settings (
    sip_profile_setting_uuid uuid NOT NULL,
    sip_profile_uuid uuid,
    sip_profile_setting_name text,
    sip_profile_setting_value text,
    sip_profile_setting_enabled boolean,
    sip_profile_setting_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_sip_profile_settings OWNER TO fusionpbx;

--
-- Name: v_sip_profiles; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_sip_profiles (
    sip_profile_uuid uuid NOT NULL,
    sip_profile_name text,
    sip_profile_hostname text,
    sip_profile_enabled boolean,
    sip_profile_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_sip_profiles OWNER TO fusionpbx;

--
-- Name: v_sofia_global_settings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_sofia_global_settings (
    sofia_global_setting_uuid uuid NOT NULL,
    global_setting_name text,
    global_setting_value text,
    global_setting_enabled boolean,
    global_setting_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_sofia_global_settings OWNER TO fusionpbx;

--
-- Name: v_software; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_software (
    software_uuid uuid NOT NULL,
    software_name text,
    software_url text,
    software_version text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_software OWNER TO fusionpbx;

--
-- Name: v_streams; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_streams (
    stream_uuid uuid NOT NULL,
    domain_uuid uuid,
    stream_name text,
    stream_location text,
    stream_enabled boolean,
    stream_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_streams OWNER TO fusionpbx;

--
-- Name: v_user_groups; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_user_groups (
    user_group_uuid uuid NOT NULL,
    domain_uuid uuid,
    group_name text,
    group_uuid uuid,
    user_uuid uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_user_groups OWNER TO fusionpbx;

--
-- Name: v_user_logs; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_user_logs (
    user_log_uuid uuid NOT NULL,
    domain_uuid uuid,
    hostname text,
    "timestamp" timestamp with time zone,
    user_uuid uuid,
    username text,
    type text,
    result text,
    detail text,
    remote_address text,
    user_agent text,
    session_id text,
    remember_selector uuid,
    remember_validator text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_user_logs OWNER TO fusionpbx;

--
-- Name: v_user_settings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_user_settings (
    user_setting_uuid uuid NOT NULL,
    user_uuid uuid,
    domain_uuid uuid,
    user_setting_category text,
    user_setting_subcategory text,
    user_setting_name text,
    user_setting_value text,
    user_setting_order numeric,
    user_setting_enabled boolean,
    user_setting_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_user_settings OWNER TO fusionpbx;

--
-- Name: v_users; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_users (
    user_uuid uuid NOT NULL,
    domain_uuid uuid,
    contact_uuid uuid,
    username text,
    password text,
    salt text,
    user_email text,
    user_status text,
    api_key text,
    user_totp_secret text,
    user_type text,
    user_enabled boolean,
    add_user text,
    add_date text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_users OWNER TO fusionpbx;

--
-- Name: v_vars; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_vars (
    var_uuid uuid NOT NULL,
    var_category text,
    var_name text,
    var_value text,
    var_command text,
    var_hostname text,
    var_enabled boolean,
    var_order numeric,
    var_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_vars OWNER TO fusionpbx;

--
-- Name: v_voicemail_destinations; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_voicemail_destinations (
    domain_uuid uuid,
    voicemail_destination_uuid uuid NOT NULL,
    voicemail_uuid uuid,
    voicemail_uuid_copy uuid,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_voicemail_destinations OWNER TO fusionpbx;

--
-- Name: v_voicemail_greetings; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_voicemail_greetings (
    voicemail_greeting_uuid uuid NOT NULL,
    domain_uuid uuid,
    voicemail_id text,
    greeting_id numeric,
    greeting_name text,
    greeting_voice text,
    greeting_message text,
    greeting_filename text,
    greeting_description text,
    greeting_base64 text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_voicemail_greetings OWNER TO fusionpbx;

--
-- Name: v_voicemail_messages; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_voicemail_messages (
    domain_uuid uuid,
    voicemail_message_uuid uuid NOT NULL,
    voicemail_uuid uuid,
    created_epoch numeric,
    read_epoch numeric,
    caller_id_name text,
    caller_id_number text,
    message_length numeric,
    message_status text,
    message_priority text,
    message_intro_base64 text,
    message_base64 text,
    message_transcription text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_voicemail_messages OWNER TO fusionpbx;

--
-- Name: v_voicemail_options; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_voicemail_options (
    voicemail_option_uuid uuid NOT NULL,
    domain_uuid uuid,
    voicemail_uuid uuid,
    voicemail_option_digits text,
    voicemail_option_action text,
    voicemail_option_param text,
    voicemail_option_order numeric,
    voicemail_option_description text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_voicemail_options OWNER TO fusionpbx;

--
-- Name: v_voicemails; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_voicemails (
    domain_uuid uuid,
    voicemail_uuid uuid NOT NULL,
    voicemail_id text,
    voicemail_password text,
    voicemail_tutorial boolean,
    greeting_id numeric,
    voicemail_alternate_greet_id text,
    voicemail_recording_instructions boolean,
    voicemail_recording_options boolean,
    voicemail_mail_to text,
    voicemail_sms_to text,
    voicemail_transcription_enabled boolean,
    voicemail_attach_file text,
    voicemail_file text,
    voicemail_local_after_email boolean,
    voicemail_local_after_forward boolean,
    voicemail_enabled boolean,
    voicemail_description text,
    voicemail_name_base64 text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_voicemails OWNER TO fusionpbx;

--
-- Name: v_xml_cdr; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_xml_cdr (
    xml_cdr_uuid uuid NOT NULL,
    domain_uuid uuid,
    provider_uuid uuid,
    extension_uuid uuid,
    sip_call_id text,
    domain_name text,
    accountcode text,
    direction text,
    default_language text,
    context text,
    caller_id_name text,
    caller_id_number text,
    caller_destination text,
    source_number text,
    destination_number text,
    start_epoch numeric,
    start_stamp timestamp with time zone,
    answer_stamp timestamp with time zone,
    answer_epoch numeric,
    end_epoch numeric,
    end_stamp timestamp with time zone,
    duration numeric,
    mduration numeric,
    billsec numeric,
    billmsec numeric,
    hold_accum_seconds numeric,
    bridge_uuid text,
    read_codec text,
    read_rate text,
    write_codec text,
    write_rate text,
    remote_media_ip text,
    network_addr text,
    record_path text,
    record_name text,
    record_length numeric,
    record_transcription text,
    leg character(1),
    originating_leg_uuid uuid,
    pdd_ms numeric,
    rtp_audio_in_mos numeric,
    last_app text,
    last_arg text,
    voicemail_message boolean,
    missed_call boolean,
    call_center_queue_uuid uuid,
    cc_side text,
    cc_member_uuid uuid,
    cc_queue_joined_epoch numeric,
    cc_queue text,
    cc_member_session_uuid uuid,
    cc_agent_uuid uuid,
    cc_agent text,
    cc_agent_type text,
    cc_agent_bridged text,
    cc_queue_answered_epoch numeric,
    cc_queue_terminated_epoch numeric,
    cc_queue_canceled_epoch numeric,
    cc_cancel_reason text,
    cc_cause text,
    waitsec numeric,
    conference_name text,
    conference_uuid uuid,
    conference_member_id text,
    digits_dialed text,
    pin_number text,
    status text,
    call_disposition text,
    hangup_cause text,
    hangup_cause_q850 numeric,
    sip_hangup_disposition text,
    ring_group_uuid uuid,
    ivr_menu_uuid uuid,
    processed boolean,
    call_flow jsonb,
    xml text,
    json jsonb,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_xml_cdr OWNER TO fusionpbx;

--
-- Name: v_xml_cdr_extensions; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_xml_cdr_extensions (
    xml_cdr_extension_uuid uuid NOT NULL,
    domain_uuid uuid,
    xml_cdr_uuid uuid,
    extension_uuid uuid,
    start_stamp timestamp with time zone,
    end_stamp timestamp with time zone,
    duration numeric,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_xml_cdr_extensions OWNER TO fusionpbx;

--
-- Name: v_xml_cdr_flow; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_xml_cdr_flow (
    xml_cdr_flow_uuid uuid NOT NULL,
    xml_cdr_uuid uuid,
    domain_uuid uuid,
    call_flow jsonb,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_xml_cdr_flow OWNER TO fusionpbx;

--
-- Name: v_xml_cdr_json; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_xml_cdr_json (
    xml_cdr_json_uuid uuid NOT NULL,
    xml_cdr_uuid uuid,
    domain_uuid uuid,
    start_stamp timestamp with time zone,
    json jsonb,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_xml_cdr_json OWNER TO fusionpbx;

--
-- Name: v_xml_cdr_logs; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_xml_cdr_logs (
    xml_cdr_log_uuid uuid NOT NULL,
    xml_cdr_uuid uuid,
    domain_uuid uuid,
    log_date text,
    log_content text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_xml_cdr_logs OWNER TO fusionpbx;

--
-- Name: v_xml_cdr_transcripts; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.v_xml_cdr_transcripts (
    xml_cdr_transcript_uuid uuid NOT NULL,
    xml_cdr_uuid uuid,
    domain_uuid uuid,
    transcript_json jsonb,
    transcript_summary text,
    insert_date timestamp with time zone,
    insert_user uuid,
    update_date timestamp with time zone,
    update_user uuid
);


ALTER TABLE public.v_xml_cdr_transcripts OWNER TO fusionpbx;

--
-- Name: view_call_block; Type: VIEW; Schema: public; Owner: fusionpbx
--

CREATE VIEW public.view_call_block AS
 SELECT c.domain_uuid,
    c.call_block_uuid,
    c.call_block_direction,
    c.extension_uuid,
    c.call_block_name,
    c.call_block_country_code,
    c.call_block_number,
    e.extension,
    e.number_alias,
    c.call_block_count,
    c.call_block_app,
    c.call_block_data,
    c.date_added,
    c.call_block_enabled,
    c.call_block_description,
    c.insert_date,
    c.insert_user,
    c.update_date,
    c.update_user
   FROM (public.v_call_block c
     LEFT JOIN public.v_extensions e ON ((c.extension_uuid = e.extension_uuid)));


ALTER TABLE public.view_call_block OWNER TO fusionpbx;

--
-- Name: view_call_recordings; Type: VIEW; Schema: public; Owner: fusionpbx
--

CREATE VIEW public.view_call_recordings AS
 SELECT c.domain_uuid,
    c.xml_cdr_uuid AS call_recording_uuid,
    c.caller_id_name,
    c.caller_id_number,
    c.caller_destination,
    c.destination_number,
    c.record_name AS call_recording_name,
    c.record_path AS call_recording_path,
    t.transcript_json AS call_recording_transcription,
    c.duration AS call_recording_length,
    c.start_stamp AS call_recording_date,
    c.direction AS call_direction
   FROM (public.v_xml_cdr c
     LEFT JOIN public.v_xml_cdr_transcripts t ON ((c.xml_cdr_uuid = t.xml_cdr_uuid)))
  WHERE ((c.record_name IS NOT NULL) AND (c.record_path IS NOT NULL) AND (c.hangup_cause <> 'LOSE_RACE'::text))
  ORDER BY c.start_stamp DESC;


ALTER TABLE public.view_call_recordings OWNER TO fusionpbx;

--
-- Name: view_contacts; Type: VIEW; Schema: public; Owner: fusionpbx
--

CREATE VIEW public.view_contacts AS
 SELECT c.contact_uuid,
    c.domain_uuid,
    c.contact_parent_uuid,
    c.contact_type,
    c.contact_organization,
    c.contact_name_prefix,
    c.contact_name_given,
    c.contact_name_middle,
    c.contact_name_family,
    c.contact_name_suffix,
    c.contact_nickname,
    c.contact_title,
    c.contact_role,
    c.contact_category,
    c.contact_url,
    c.contact_time_zone,
    c.contact_note,
    c.last_mod_date,
    c.last_mod_user,
    c.insert_date,
    c.insert_user,
    c.update_date,
    c.update_user,
    ( SELECT json_agg(a.*) AS json_agg
           FROM public.v_contact_addresses a
          WHERE (a.contact_uuid = c.contact_uuid)) AS contact_addresses,
    ( SELECT json_agg(p.*) AS json_agg
           FROM public.v_contact_phones p
          WHERE (p.contact_uuid = c.contact_uuid)) AS contact_phones,
    ( SELECT json_agg(e.*) AS json_agg
           FROM public.v_contact_emails e
          WHERE (e.contact_uuid = c.contact_uuid)) AS contact_emails,
    ( SELECT json_agg(l.*) AS json_agg
           FROM public.v_contact_urls l
          WHERE (l.contact_uuid = c.contact_uuid)) AS contact_urls,
    ( SELECT json_agg(u.*) AS json_agg
           FROM public.v_contact_users u
          WHERE (u.contact_uuid = c.contact_uuid)) AS contact_users,
    ( SELECT json_agg(g.*) AS json_agg
           FROM public.v_contact_groups g
          WHERE (g.contact_uuid = c.contact_uuid)) AS contact_groups,
    ( SELECT json_agg(s.*) AS json_agg
           FROM public.v_contact_settings s
          WHERE (s.contact_uuid = c.contact_uuid)) AS contact_settings,
    ( SELECT json_agg(r.*) AS json_agg
           FROM public.v_contact_relations r
          WHERE (r.contact_uuid = c.contact_uuid)) AS contact_relations
   FROM public.v_contacts c;


ALTER TABLE public.view_contacts OWNER TO fusionpbx;

--
-- Name: view_extensions; Type: VIEW; Schema: public; Owner: fusionpbx
--

CREATE VIEW public.view_extensions AS
 SELECT e.extension_uuid,
    e.domain_uuid,
    e.extension,
    e.number_alias,
    e.password,
    e.accountcode,
    e.effective_caller_id_name,
    e.effective_caller_id_number,
    e.outbound_caller_id_name,
    e.outbound_caller_id_number,
    e.emergency_caller_id_name,
    e.emergency_caller_id_number,
    e.directory_first_name,
    e.directory_last_name,
    e.directory_visible,
    e.directory_exten_visible,
    e.max_registrations,
    e.limit_max,
    e.limit_destination,
    e.missed_call_app,
    e.missed_call_data,
    e.user_context,
    e.toll_allow,
    e.call_timeout,
    e.call_group,
    e.call_screen_enabled,
    e.user_record,
    e.hold_music,
    e.auth_acl,
    e.cidr,
    e.sip_force_contact,
    e.nibble_account,
    e.sip_force_expires,
    e.mwi_account,
    e.sip_bypass_media,
    e.unique_id,
    e.dial_string,
    e.dial_user,
    e.dial_domain,
    e.do_not_disturb,
    e.forward_all_destination,
    e.forward_all_enabled,
    e.forward_busy_destination,
    e.forward_busy_enabled,
    e.forward_no_answer_destination,
    e.forward_no_answer_enabled,
    e.forward_user_not_registered_destination,
    e.forward_user_not_registered_enabled,
    e.follow_me_uuid,
    e.follow_me_enabled,
    e.follow_me_destinations,
    e.extension_language,
    e.extension_dialect,
    e.extension_voice,
    e.extension_type,
    e.enabled,
    e.description,
    e.absolute_codec_string,
    e.force_ping,
    e.insert_date,
    e.insert_user,
    e.update_date,
    e.update_user,
    ( SELECT json_agg(u.*) AS json_agg
           FROM public.v_extension_users u
          WHERE (u.extension_uuid = e.extension_uuid)) AS extension_users
   FROM public.v_extensions e;


ALTER TABLE public.view_extensions OWNER TO fusionpbx;

--
-- Name: view_groups; Type: VIEW; Schema: public; Owner: fusionpbx
--

CREATE VIEW public.view_groups AS
 SELECT g.domain_uuid,
    g.group_uuid,
    g.group_name,
    ( SELECT v_domains.domain_name
           FROM public.v_domains
          WHERE (v_domains.domain_uuid = g.domain_uuid)) AS domain_name,
    ( SELECT count(*) AS count
           FROM public.v_group_permissions
          WHERE (v_group_permissions.group_uuid = g.group_uuid)) AS group_permissions,
    ( SELECT count(*) AS count
           FROM public.v_user_groups
          WHERE (v_user_groups.group_uuid = g.group_uuid)) AS group_members,
    g.group_level,
    g.group_protected,
    g.group_description
   FROM public.v_groups g;


ALTER TABLE public.view_groups OWNER TO fusionpbx;

--
-- Name: view_music_on_hold_map; Type: VIEW; Schema: public; Owner: fusionpbx
--

CREATE VIEW public.view_music_on_hold_map AS
 SELECT 'extensions'::text AS application,
    'hold_music'::text AS type,
    e.extension_uuid AS uuid,
    e.domain_uuid,
    d.domain_name,
    e.effective_caller_id_name AS name,
    e.extension AS number,
    e.hold_music AS music,
    e.description
   FROM public.v_extensions e,
    public.v_domains d
  WHERE ((e.hold_music ~~ '%local_stream%'::text) AND (e.domain_uuid = d.domain_uuid))
UNION
 SELECT 'ring_groups'::text AS application,
    'ringback'::text AS type,
    r.ring_group_uuid AS uuid,
    r.domain_uuid,
    d.domain_name,
    r.ring_group_name AS name,
    r.ring_group_extension AS number,
    r.ring_group_ringback AS music,
    r.ring_group_description AS description
   FROM public.v_ring_groups r,
    public.v_domains d
  WHERE ((r.ring_group_ringback ~~ '%local_stream%'::text) AND (r.domain_uuid = d.domain_uuid))
UNION
 SELECT 'ivr_menus'::text AS application,
    'ringback'::text AS type,
    i.ivr_menu_uuid AS uuid,
    i.domain_uuid,
    d.domain_name,
    i.ivr_menu_name AS name,
    i.ivr_menu_extension AS number,
    i.ivr_menu_ringback AS music,
    i.ivr_menu_description AS description
   FROM public.v_ivr_menus i,
    public.v_domains d
  WHERE ((i.ivr_menu_ringback ~~ '%local_stream%'::text) AND (i.domain_uuid = d.domain_uuid))
UNION
 SELECT 'call_center_queues'::text AS application,
    'hold_music'::text AS type,
    q.call_center_queue_uuid AS uuid,
    q.domain_uuid,
    d.domain_name,
    q.queue_name AS name,
    q.queue_extension AS number,
    q.queue_moh_sound AS music,
    q.queue_description AS description
   FROM public.v_call_center_queues q,
    public.v_domains d
  WHERE ((q.queue_moh_sound ~~ '%local_stream%'::text) AND (q.domain_uuid = d.domain_uuid))
UNION
 SELECT 'fifo'::text AS application,
    'hold_music'::text AS type,
    f.fifo_uuid AS uuid,
    f.domain_uuid,
    d.domain_name,
    f.fifo_name AS name,
    f.fifo_extension AS number,
    f.fifo_music AS music,
    f.fifo_description AS description
   FROM public.v_fifo f,
    public.v_domains d
  WHERE ((f.fifo_music ~~ '%local_stream%'::text) AND (f.domain_uuid = d.domain_uuid))
UNION
 SELECT 'destinations'::text AS application,
    'hold_music'::text AS type,
    e.destination_uuid AS uuid,
    e.domain_uuid,
    d.domain_name,
    ''::text AS name,
    e.destination_number AS number,
    e.destination_hold_music AS music,
    e.destination_description AS description
   FROM public.v_destinations e,
    public.v_domains d
  WHERE ((e.destination_hold_music ~~ '%local_stream%'::text) AND (e.domain_uuid = d.domain_uuid))
UNION
 SELECT 'destinations'::text AS application,
    'ringback'::text AS type,
    e.destination_uuid AS uuid,
    e.domain_uuid,
    d.domain_name,
    ''::text AS name,
    e.destination_number AS number,
    e.destination_ringback AS music,
    e.destination_description AS description
   FROM public.v_destinations e,
    public.v_domains d
  WHERE ((e.destination_ringback ~~ '%local_stream%'::text) AND (e.domain_uuid = d.domain_uuid))
UNION
 SELECT 'dialplans'::text AS application,
    'unknown'::text AS type,
    dl.dialplan_uuid AS uuid,
    dl.domain_uuid,
    d.domain_name,
    dl.dialplan_name AS name,
    dl.dialplan_number AS number,
    de.dialplan_detail_data AS music,
    dl.dialplan_description AS description
   FROM public.v_dialplan_details de,
    public.v_domains d,
    public.v_dialplans dl
  WHERE ((de.dialplan_detail_data ~~ '%local_stream%'::text) AND (de.domain_uuid = d.domain_uuid) AND (dl.dialplan_uuid = de.dialplan_uuid));


ALTER TABLE public.view_music_on_hold_map OWNER TO fusionpbx;

--
-- Name: view_stream_map; Type: VIEW; Schema: public; Owner: fusionpbx
--

CREATE VIEW public.view_stream_map AS
 SELECT 'extensions'::text AS application,
    'hold_music'::text AS type,
    e.extension_uuid AS uuid,
    e.domain_uuid,
    d.domain_name,
    e.effective_caller_id_name AS name,
    e.extension AS number,
    e.hold_music AS music,
    e.description
   FROM public.v_extensions e,
    public.v_domains d
  WHERE ((e.hold_music ~~ '%shout%'::text) AND (e.domain_uuid = d.domain_uuid))
UNION
 SELECT 'ring_groups'::text AS application,
    'ringback'::text AS type,
    r.ring_group_uuid AS uuid,
    r.domain_uuid,
    d.domain_name,
    r.ring_group_name AS name,
    r.ring_group_extension AS number,
    r.ring_group_ringback AS music,
    r.ring_group_description AS description
   FROM public.v_ring_groups r,
    public.v_domains d
  WHERE ((r.ring_group_ringback ~~ '%shout%'::text) AND (r.domain_uuid = d.domain_uuid))
UNION
 SELECT 'ivr_menus'::text AS application,
    'ringback'::text AS type,
    i.ivr_menu_uuid AS uuid,
    i.domain_uuid,
    d.domain_name,
    i.ivr_menu_name AS name,
    i.ivr_menu_extension AS number,
    i.ivr_menu_ringback AS music,
    i.ivr_menu_description AS description
   FROM public.v_ivr_menus i,
    public.v_domains d
  WHERE ((i.ivr_menu_ringback ~~ '%shout%'::text) AND (i.domain_uuid = d.domain_uuid))
UNION
 SELECT 'call_center_queues'::text AS application,
    'hold_music'::text AS type,
    q.call_center_queue_uuid AS uuid,
    q.domain_uuid,
    d.domain_name,
    q.queue_name AS name,
    q.queue_extension AS number,
    q.queue_moh_sound AS music,
    q.queue_description AS description
   FROM public.v_call_center_queues q,
    public.v_domains d
  WHERE ((q.queue_moh_sound ~~ '%shout%'::text) AND (q.domain_uuid = d.domain_uuid))
UNION
 SELECT 'fifo'::text AS application,
    'hold_music'::text AS type,
    f.fifo_uuid AS uuid,
    f.domain_uuid,
    d.domain_name,
    f.fifo_name AS name,
    f.fifo_extension AS number,
    f.fifo_music AS music,
    f.fifo_description AS description
   FROM public.v_fifo f,
    public.v_domains d
  WHERE ((f.fifo_music ~~ '%shout%'::text) AND (f.domain_uuid = d.domain_uuid))
UNION
 SELECT 'destinations'::text AS application,
    'hold_music'::text AS type,
    e.destination_uuid AS uuid,
    e.domain_uuid,
    d.domain_name,
    ''::text AS name,
    e.destination_number AS number,
    e.destination_hold_music AS music,
    e.destination_description AS description
   FROM public.v_destinations e,
    public.v_domains d
  WHERE ((e.destination_hold_music ~~ '%shout%'::text) AND (e.domain_uuid = d.domain_uuid))
UNION
 SELECT 'destinations'::text AS application,
    'ringback'::text AS type,
    e.destination_uuid AS uuid,
    e.domain_uuid,
    d.domain_name,
    ''::text AS name,
    e.destination_number AS number,
    e.destination_ringback AS music,
    e.destination_description AS description
   FROM public.v_destinations e,
    public.v_domains d
  WHERE ((e.destination_ringback ~~ '%shout%'::text) AND (e.domain_uuid = d.domain_uuid))
UNION
 SELECT 'dialplans'::text AS application,
    'unknown'::text AS type,
    dl.dialplan_uuid AS uuid,
    dl.domain_uuid,
    d.domain_name,
    dl.dialplan_name AS name,
    dl.dialplan_number AS number,
    de.dialplan_detail_data AS music,
    dl.dialplan_description AS description
   FROM public.v_dialplan_details de,
    public.v_domains d,
    public.v_dialplans dl
  WHERE ((de.dialplan_detail_data ~~ '%shout%'::text) AND (de.domain_uuid = d.domain_uuid) AND (dl.dialplan_uuid = de.dialplan_uuid));


ALTER TABLE public.view_stream_map OWNER TO fusionpbx;

--
-- Name: view_users; Type: VIEW; Schema: public; Owner: fusionpbx
--

CREATE VIEW public.view_users AS
 SELECT u.domain_uuid,
    u.user_uuid,
    d.domain_name,
    u.username,
    u.user_status,
    u.user_enabled,
    u.add_date,
    c.contact_uuid,
    c.contact_organization,
    ((c.contact_name_given || ' '::text) || c.contact_name_family) AS contact_name,
    c.contact_name_given,
    c.contact_name_family,
    c.contact_note,
    ( SELECT string_agg(g.group_name, ', '::text) AS string_agg
           FROM public.v_user_groups ug,
            public.v_groups g
          WHERE ((ug.group_uuid = g.group_uuid) AND (u.user_uuid = ug.user_uuid))) AS group_names,
    ( SELECT string_agg((g.group_uuid)::text, ', '::text) AS string_agg
           FROM public.v_user_groups ug,
            public.v_groups g
          WHERE ((ug.group_uuid = g.group_uuid) AND (u.user_uuid = ug.user_uuid))) AS group_uuids,
    ( SELECT g.group_level
           FROM public.v_user_groups ug,
            public.v_groups g
          WHERE ((ug.group_uuid = g.group_uuid) AND (u.user_uuid = ug.user_uuid))
          ORDER BY g.group_level DESC
         LIMIT 1) AS group_level
   FROM ((public.v_contacts c
     RIGHT JOIN public.v_users u ON ((u.contact_uuid = c.contact_uuid)))
     JOIN public.v_domains d ON ((d.domain_uuid = u.domain_uuid)))
  WHERE (1 = 1)
  ORDER BY u.username;


ALTER TABLE public.view_users OWNER TO fusionpbx;

--
-- Name: view_xml_cdr; Type: VIEW; Schema: public; Owner: fusionpbx
--

CREATE VIEW public.view_xml_cdr AS
 SELECT c.xml_cdr_uuid,
    c.domain_uuid,
    c.provider_uuid,
    c.extension_uuid,
    c.sip_call_id,
    c.domain_name,
    c.accountcode,
    c.direction,
    c.default_language,
    c.context,
    c.caller_id_name,
    c.caller_id_number,
    c.caller_destination,
    c.source_number,
    c.destination_number,
    c.start_epoch,
    c.start_stamp,
    c.answer_stamp,
    c.answer_epoch,
    c.end_epoch,
    c.end_stamp,
    c.duration,
    c.mduration,
    c.billsec,
    c.billmsec,
    c.bridge_uuid,
    c.read_codec,
    c.read_rate,
    c.write_codec,
    c.write_rate,
    c.remote_media_ip,
    c.network_addr,
    c.record_path,
    c.record_name,
    c.record_length,
    c.leg,
    c.originating_leg_uuid,
    c.pdd_ms,
    c.rtp_audio_in_mos,
    c.last_app,
    c.last_arg,
    c.voicemail_message,
    c.missed_call,
    c.call_center_queue_uuid,
    c.cc_side,
    c.cc_member_uuid,
    c.cc_queue_joined_epoch,
    c.cc_queue,
    c.cc_member_session_uuid,
    c.cc_agent_uuid,
    c.cc_agent,
    c.cc_agent_type,
    c.cc_agent_bridged,
    c.cc_queue_answered_epoch,
    c.cc_queue_terminated_epoch,
    c.cc_queue_canceled_epoch,
    c.cc_cancel_reason,
    c.cc_cause,
    c.waitsec,
    c.conference_name,
    c.conference_uuid,
    c.conference_member_id,
    c.digits_dialed,
    c.pin_number,
    c.status,
    c.hangup_cause,
    c.hangup_cause_q850,
    c.sip_hangup_disposition,
    c.processed,
    c.call_flow,
    c.xml,
    c.insert_date,
    c.insert_user,
    c.update_date,
    c.update_user,
        CASE
            WHEN (c.json IS NOT NULL) THEN c.json
            ELSE j.json
        END AS json
   FROM public.v_xml_cdr c,
    public.v_xml_cdr_json j
  WHERE (c.xml_cdr_uuid = j.xml_cdr_uuid);


ALTER TABLE public.view_xml_cdr OWNER TO fusionpbx;

--
-- Name: v_access_control_nodes v_access_control_nodes_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_access_control_nodes
    ADD CONSTRAINT v_access_control_nodes_pkey PRIMARY KEY (access_control_node_uuid);


--
-- Name: v_access_controls v_access_controls_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_access_controls
    ADD CONSTRAINT v_access_controls_pkey PRIMARY KEY (access_control_uuid);


--
-- Name: v_bridges v_bridges_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_bridges
    ADD CONSTRAINT v_bridges_pkey PRIMARY KEY (bridge_uuid);


--
-- Name: v_call_block v_call_block_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_call_block
    ADD CONSTRAINT v_call_block_pkey PRIMARY KEY (call_block_uuid);


--
-- Name: v_call_broadcasts v_call_broadcasts_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_call_broadcasts
    ADD CONSTRAINT v_call_broadcasts_pkey PRIMARY KEY (call_broadcast_uuid);


--
-- Name: v_call_center_agents v_call_center_agents_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_call_center_agents
    ADD CONSTRAINT v_call_center_agents_pkey PRIMARY KEY (call_center_agent_uuid);


--
-- Name: v_call_center_queues v_call_center_queues_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_call_center_queues
    ADD CONSTRAINT v_call_center_queues_pkey PRIMARY KEY (call_center_queue_uuid);


--
-- Name: v_call_center_tiers v_call_center_tiers_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_call_center_tiers
    ADD CONSTRAINT v_call_center_tiers_pkey PRIMARY KEY (call_center_tier_uuid);


--
-- Name: v_call_flows v_call_flows_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_call_flows
    ADD CONSTRAINT v_call_flows_pkey PRIMARY KEY (call_flow_uuid);


--
-- Name: v_conference_centers v_conference_centers_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conference_centers
    ADD CONSTRAINT v_conference_centers_pkey PRIMARY KEY (conference_center_uuid);


--
-- Name: v_conference_control_details v_conference_control_details_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conference_control_details
    ADD CONSTRAINT v_conference_control_details_pkey PRIMARY KEY (conference_control_detail_uuid);


--
-- Name: v_conference_controls v_conference_controls_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conference_controls
    ADD CONSTRAINT v_conference_controls_pkey PRIMARY KEY (conference_control_uuid);


--
-- Name: v_conference_profile_params v_conference_profile_params_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conference_profile_params
    ADD CONSTRAINT v_conference_profile_params_pkey PRIMARY KEY (conference_profile_param_uuid);


--
-- Name: v_conference_profiles v_conference_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conference_profiles
    ADD CONSTRAINT v_conference_profiles_pkey PRIMARY KEY (conference_profile_uuid);


--
-- Name: v_conference_room_users v_conference_room_users_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conference_room_users
    ADD CONSTRAINT v_conference_room_users_pkey PRIMARY KEY (conference_room_user_uuid);


--
-- Name: v_conference_rooms v_conference_rooms_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conference_rooms
    ADD CONSTRAINT v_conference_rooms_pkey PRIMARY KEY (conference_room_uuid);


--
-- Name: v_conference_session_details v_conference_session_details_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conference_session_details
    ADD CONSTRAINT v_conference_session_details_pkey PRIMARY KEY (conference_session_detail_uuid);


--
-- Name: v_conference_sessions v_conference_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conference_sessions
    ADD CONSTRAINT v_conference_sessions_pkey PRIMARY KEY (conference_session_uuid);


--
-- Name: v_conference_users v_conference_users_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conference_users
    ADD CONSTRAINT v_conference_users_pkey PRIMARY KEY (conference_user_uuid);


--
-- Name: v_conferences v_conferences_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_conferences
    ADD CONSTRAINT v_conferences_pkey PRIMARY KEY (conference_uuid);


--
-- Name: v_contact_addresses v_contact_addresses_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_addresses
    ADD CONSTRAINT v_contact_addresses_pkey PRIMARY KEY (contact_address_uuid);


--
-- Name: v_contact_attachments v_contact_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_attachments
    ADD CONSTRAINT v_contact_attachments_pkey PRIMARY KEY (contact_attachment_uuid);


--
-- Name: v_contact_emails v_contact_emails_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_emails
    ADD CONSTRAINT v_contact_emails_pkey PRIMARY KEY (contact_email_uuid);


--
-- Name: v_contact_groups v_contact_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_groups
    ADD CONSTRAINT v_contact_groups_pkey PRIMARY KEY (contact_group_uuid);


--
-- Name: v_contact_notes v_contact_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_notes
    ADD CONSTRAINT v_contact_notes_pkey PRIMARY KEY (contact_note_uuid);


--
-- Name: v_contact_phones v_contact_phones_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_phones
    ADD CONSTRAINT v_contact_phones_pkey PRIMARY KEY (contact_phone_uuid);


--
-- Name: v_contact_relations v_contact_relations_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_relations
    ADD CONSTRAINT v_contact_relations_pkey PRIMARY KEY (contact_relation_uuid);


--
-- Name: v_contact_settings v_contact_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_settings
    ADD CONSTRAINT v_contact_settings_pkey PRIMARY KEY (contact_setting_uuid);


--
-- Name: v_contact_times v_contact_times_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_times
    ADD CONSTRAINT v_contact_times_pkey PRIMARY KEY (contact_time_uuid);


--
-- Name: v_contact_urls v_contact_urls_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_urls
    ADD CONSTRAINT v_contact_urls_pkey PRIMARY KEY (contact_url_uuid);


--
-- Name: v_contact_users v_contact_users_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contact_users
    ADD CONSTRAINT v_contact_users_pkey PRIMARY KEY (contact_user_uuid);


--
-- Name: v_contacts v_contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_contacts
    ADD CONSTRAINT v_contacts_pkey PRIMARY KEY (contact_uuid);


--
-- Name: v_countries v_countries_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_countries
    ADD CONSTRAINT v_countries_pkey PRIMARY KEY (country_uuid);


--
-- Name: v_dashboard_widget_groups v_dashboard_widget_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_dashboard_widget_groups
    ADD CONSTRAINT v_dashboard_widget_groups_pkey PRIMARY KEY (dashboard_widget_group_uuid);


--
-- Name: v_dashboard_widgets v_dashboard_widgets_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_dashboard_widgets
    ADD CONSTRAINT v_dashboard_widgets_pkey PRIMARY KEY (dashboard_widget_uuid);


--
-- Name: v_dashboards v_dashboards_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_dashboards
    ADD CONSTRAINT v_dashboards_pkey PRIMARY KEY (dashboard_uuid);


--
-- Name: v_database_transactions v_database_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_database_transactions
    ADD CONSTRAINT v_database_transactions_pkey PRIMARY KEY (database_transaction_uuid);


--
-- Name: v_databases v_databases_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_databases
    ADD CONSTRAINT v_databases_pkey PRIMARY KEY (database_uuid);


--
-- Name: v_default_settings v_default_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_default_settings
    ADD CONSTRAINT v_default_settings_pkey PRIMARY KEY (default_setting_uuid);


--
-- Name: v_destinations v_destinations_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_destinations
    ADD CONSTRAINT v_destinations_pkey PRIMARY KEY (destination_uuid);


--
-- Name: v_device_keys v_device_keys_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_device_keys
    ADD CONSTRAINT v_device_keys_pkey PRIMARY KEY (device_key_uuid);


--
-- Name: v_device_lines v_device_lines_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_device_lines
    ADD CONSTRAINT v_device_lines_pkey PRIMARY KEY (device_line_uuid);


--
-- Name: v_device_profile_keys v_device_profile_keys_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_device_profile_keys
    ADD CONSTRAINT v_device_profile_keys_pkey PRIMARY KEY (device_profile_key_uuid);


--
-- Name: v_device_profile_settings v_device_profile_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_device_profile_settings
    ADD CONSTRAINT v_device_profile_settings_pkey PRIMARY KEY (device_profile_setting_uuid);


--
-- Name: v_device_profiles v_device_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_device_profiles
    ADD CONSTRAINT v_device_profiles_pkey PRIMARY KEY (device_profile_uuid);


--
-- Name: v_device_settings v_device_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_device_settings
    ADD CONSTRAINT v_device_settings_pkey PRIMARY KEY (device_setting_uuid);


--
-- Name: v_device_vendor_function_groups v_device_vendor_function_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_device_vendor_function_groups
    ADD CONSTRAINT v_device_vendor_function_groups_pkey PRIMARY KEY (device_vendor_function_group_uuid);


--
-- Name: v_device_vendor_functions v_device_vendor_functions_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_device_vendor_functions
    ADD CONSTRAINT v_device_vendor_functions_pkey PRIMARY KEY (device_vendor_function_uuid);


--
-- Name: v_device_vendors v_device_vendors_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_device_vendors
    ADD CONSTRAINT v_device_vendors_pkey PRIMARY KEY (device_vendor_uuid);


--
-- Name: v_devices v_devices_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_devices
    ADD CONSTRAINT v_devices_pkey PRIMARY KEY (device_uuid);


--
-- Name: v_dialplan_details v_dialplan_details_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_dialplan_details
    ADD CONSTRAINT v_dialplan_details_pkey PRIMARY KEY (dialplan_detail_uuid);


--
-- Name: v_dialplans v_dialplans_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_dialplans
    ADD CONSTRAINT v_dialplans_pkey PRIMARY KEY (dialplan_uuid);


--
-- Name: v_domain_settings v_domain_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_domain_settings
    ADD CONSTRAINT v_domain_settings_pkey PRIMARY KEY (domain_setting_uuid);


--
-- Name: v_domains v_domains_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_domains
    ADD CONSTRAINT v_domains_pkey PRIMARY KEY (domain_uuid);


--
-- Name: v_email_queue_attachments v_email_queue_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_email_queue_attachments
    ADD CONSTRAINT v_email_queue_attachments_pkey PRIMARY KEY (email_queue_attachment_uuid);


--
-- Name: v_email_queue v_email_queue_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_email_queue
    ADD CONSTRAINT v_email_queue_pkey PRIMARY KEY (email_queue_uuid);


--
-- Name: v_email_templates v_email_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_email_templates
    ADD CONSTRAINT v_email_templates_pkey PRIMARY KEY (email_template_uuid);


--
-- Name: v_emergency_logs v_emergency_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_emergency_logs
    ADD CONSTRAINT v_emergency_logs_pkey PRIMARY KEY (emergency_log_uuid);


--
-- Name: v_event_guard_logs v_event_guard_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_event_guard_logs
    ADD CONSTRAINT v_event_guard_logs_pkey PRIMARY KEY (event_guard_log_uuid);


--
-- Name: v_extension_settings v_extension_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_extension_settings
    ADD CONSTRAINT v_extension_settings_pkey PRIMARY KEY (extension_setting_uuid);


--
-- Name: v_extension_users v_extension_users_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_extension_users
    ADD CONSTRAINT v_extension_users_pkey PRIMARY KEY (extension_user_uuid);


--
-- Name: v_extensions v_extensions_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_extensions
    ADD CONSTRAINT v_extensions_pkey PRIMARY KEY (extension_uuid);


--
-- Name: v_fax_files v_fax_files_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_fax_files
    ADD CONSTRAINT v_fax_files_pkey PRIMARY KEY (fax_file_uuid);


--
-- Name: v_fax_logs v_fax_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_fax_logs
    ADD CONSTRAINT v_fax_logs_pkey PRIMARY KEY (fax_log_uuid);


--
-- Name: v_fax v_fax_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_fax
    ADD CONSTRAINT v_fax_pkey PRIMARY KEY (fax_uuid);


--
-- Name: v_fax_queue v_fax_queue_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_fax_queue
    ADD CONSTRAINT v_fax_queue_pkey PRIMARY KEY (fax_queue_uuid);


--
-- Name: v_fax_users v_fax_users_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_fax_users
    ADD CONSTRAINT v_fax_users_pkey PRIMARY KEY (fax_user_uuid);


--
-- Name: v_fifo_members v_fifo_members_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_fifo_members
    ADD CONSTRAINT v_fifo_members_pkey PRIMARY KEY (fifo_member_uuid);


--
-- Name: v_fifo v_fifo_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_fifo
    ADD CONSTRAINT v_fifo_pkey PRIMARY KEY (fifo_uuid);


--
-- Name: v_follow_me_destinations v_follow_me_destinations_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_follow_me_destinations
    ADD CONSTRAINT v_follow_me_destinations_pkey PRIMARY KEY (follow_me_destination_uuid);


--
-- Name: v_follow_me v_follow_me_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_follow_me
    ADD CONSTRAINT v_follow_me_pkey PRIMARY KEY (follow_me_uuid);


--
-- Name: v_gateways v_gateways_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_gateways
    ADD CONSTRAINT v_gateways_pkey PRIMARY KEY (gateway_uuid);


--
-- Name: v_group_permissions v_group_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_group_permissions
    ADD CONSTRAINT v_group_permissions_pkey PRIMARY KEY (group_permission_uuid);


--
-- Name: v_groups v_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_groups
    ADD CONSTRAINT v_groups_pkey PRIMARY KEY (group_uuid);


--
-- Name: v_ivr_menu_options v_ivr_menu_options_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_ivr_menu_options
    ADD CONSTRAINT v_ivr_menu_options_pkey PRIMARY KEY (ivr_menu_option_uuid);


--
-- Name: v_ivr_menus v_ivr_menus_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_ivr_menus
    ADD CONSTRAINT v_ivr_menus_pkey PRIMARY KEY (ivr_menu_uuid);


--
-- Name: v_languages v_languages_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_languages
    ADD CONSTRAINT v_languages_pkey PRIMARY KEY (language_uuid);


--
-- Name: v_menu_item_groups v_menu_item_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_menu_item_groups
    ADD CONSTRAINT v_menu_item_groups_pkey PRIMARY KEY (menu_item_group_uuid);


--
-- Name: v_menu_items v_menu_items_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_menu_items
    ADD CONSTRAINT v_menu_items_pkey PRIMARY KEY (menu_item_uuid);


--
-- Name: v_menu_languages v_menu_languages_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_menu_languages
    ADD CONSTRAINT v_menu_languages_pkey PRIMARY KEY (menu_language_uuid);


--
-- Name: v_menus v_menus_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_menus
    ADD CONSTRAINT v_menus_pkey PRIMARY KEY (menu_uuid);


--
-- Name: v_modules v_modules_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_modules
    ADD CONSTRAINT v_modules_pkey PRIMARY KEY (module_uuid);


--
-- Name: v_music_on_hold v_music_on_hold_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_music_on_hold
    ADD CONSTRAINT v_music_on_hold_pkey PRIMARY KEY (music_on_hold_uuid);


--
-- Name: v_notifications v_notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_notifications
    ADD CONSTRAINT v_notifications_pkey PRIMARY KEY (notification_uuid);


--
-- Name: v_number_translation_details v_number_translation_details_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_number_translation_details
    ADD CONSTRAINT v_number_translation_details_pkey PRIMARY KEY (number_translation_detail_uuid);


--
-- Name: v_number_translations v_number_translations_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_number_translations
    ADD CONSTRAINT v_number_translations_pkey PRIMARY KEY (number_translation_uuid);


--
-- Name: v_permissions v_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_permissions
    ADD CONSTRAINT v_permissions_pkey PRIMARY KEY (permission_uuid);


--
-- Name: v_phrase_details v_phrase_details_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_phrase_details
    ADD CONSTRAINT v_phrase_details_pkey PRIMARY KEY (phrase_detail_uuid);


--
-- Name: v_phrases v_phrases_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_phrases
    ADD CONSTRAINT v_phrases_pkey PRIMARY KEY (phrase_uuid);


--
-- Name: v_pin_numbers v_pin_numbers_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_pin_numbers
    ADD CONSTRAINT v_pin_numbers_pkey PRIMARY KEY (pin_number_uuid);


--
-- Name: v_recordings v_recordings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_recordings
    ADD CONSTRAINT v_recordings_pkey PRIMARY KEY (recording_uuid);


--
-- Name: v_ring_group_destinations v_ring_group_destinations_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_ring_group_destinations
    ADD CONSTRAINT v_ring_group_destinations_pkey PRIMARY KEY (ring_group_destination_uuid);


--
-- Name: v_ring_group_users v_ring_group_users_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_ring_group_users
    ADD CONSTRAINT v_ring_group_users_pkey PRIMARY KEY (ring_group_user_uuid);


--
-- Name: v_ring_groups v_ring_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_ring_groups
    ADD CONSTRAINT v_ring_groups_pkey PRIMARY KEY (ring_group_uuid);


--
-- Name: v_sip_profile_domains v_sip_profile_domains_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_sip_profile_domains
    ADD CONSTRAINT v_sip_profile_domains_pkey PRIMARY KEY (sip_profile_domain_uuid);


--
-- Name: v_sip_profile_settings v_sip_profile_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_sip_profile_settings
    ADD CONSTRAINT v_sip_profile_settings_pkey PRIMARY KEY (sip_profile_setting_uuid);


--
-- Name: v_sip_profiles v_sip_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_sip_profiles
    ADD CONSTRAINT v_sip_profiles_pkey PRIMARY KEY (sip_profile_uuid);


--
-- Name: v_sofia_global_settings v_sofia_global_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_sofia_global_settings
    ADD CONSTRAINT v_sofia_global_settings_pkey PRIMARY KEY (sofia_global_setting_uuid);


--
-- Name: v_software v_software_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_software
    ADD CONSTRAINT v_software_pkey PRIMARY KEY (software_uuid);


--
-- Name: v_streams v_streams_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_streams
    ADD CONSTRAINT v_streams_pkey PRIMARY KEY (stream_uuid);


--
-- Name: v_user_groups v_user_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_user_groups
    ADD CONSTRAINT v_user_groups_pkey PRIMARY KEY (user_group_uuid);


--
-- Name: v_user_logs v_user_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_user_logs
    ADD CONSTRAINT v_user_logs_pkey PRIMARY KEY (user_log_uuid);


--
-- Name: v_user_settings v_user_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_user_settings
    ADD CONSTRAINT v_user_settings_pkey PRIMARY KEY (user_setting_uuid);


--
-- Name: v_users v_users_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_users
    ADD CONSTRAINT v_users_pkey PRIMARY KEY (user_uuid);


--
-- Name: v_vars v_vars_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_vars
    ADD CONSTRAINT v_vars_pkey PRIMARY KEY (var_uuid);


--
-- Name: v_voicemail_destinations v_voicemail_destinations_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_voicemail_destinations
    ADD CONSTRAINT v_voicemail_destinations_pkey PRIMARY KEY (voicemail_destination_uuid);


--
-- Name: v_voicemail_greetings v_voicemail_greetings_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_voicemail_greetings
    ADD CONSTRAINT v_voicemail_greetings_pkey PRIMARY KEY (voicemail_greeting_uuid);


--
-- Name: v_voicemail_messages v_voicemail_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_voicemail_messages
    ADD CONSTRAINT v_voicemail_messages_pkey PRIMARY KEY (voicemail_message_uuid);


--
-- Name: v_voicemail_options v_voicemail_options_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_voicemail_options
    ADD CONSTRAINT v_voicemail_options_pkey PRIMARY KEY (voicemail_option_uuid);


--
-- Name: v_voicemails v_voicemails_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_voicemails
    ADD CONSTRAINT v_voicemails_pkey PRIMARY KEY (voicemail_uuid);


--
-- Name: v_xml_cdr_extensions v_xml_cdr_extensions_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_xml_cdr_extensions
    ADD CONSTRAINT v_xml_cdr_extensions_pkey PRIMARY KEY (xml_cdr_extension_uuid);


--
-- Name: v_xml_cdr_flow v_xml_cdr_flow_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_xml_cdr_flow
    ADD CONSTRAINT v_xml_cdr_flow_pkey PRIMARY KEY (xml_cdr_flow_uuid);


--
-- Name: v_xml_cdr_json v_xml_cdr_json_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_xml_cdr_json
    ADD CONSTRAINT v_xml_cdr_json_pkey PRIMARY KEY (xml_cdr_json_uuid);


--
-- Name: v_xml_cdr_logs v_xml_cdr_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_xml_cdr_logs
    ADD CONSTRAINT v_xml_cdr_logs_pkey PRIMARY KEY (xml_cdr_log_uuid);


--
-- Name: v_xml_cdr v_xml_cdr_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_xml_cdr
    ADD CONSTRAINT v_xml_cdr_pkey PRIMARY KEY (xml_cdr_uuid);


--
-- Name: v_xml_cdr_transcripts v_xml_cdr_transcripts_pkey; Type: CONSTRAINT; Schema: public; Owner: fusionpbx
--

ALTER TABLE ONLY public.v_xml_cdr_transcripts
    ADD CONSTRAINT v_xml_cdr_transcripts_pkey PRIMARY KEY (xml_cdr_transcript_uuid);


--
-- Name: v_access_control_nodes_access_control_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_access_control_nodes_access_control_uuid_fkey ON public.v_access_control_nodes USING btree (access_control_uuid);


--
-- Name: v_bridges_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_bridges_domain_uuid_fkey ON public.v_bridges USING btree (domain_uuid);


--
-- Name: v_call_block_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_block_domain_uuid_fkey ON public.v_call_block USING btree (domain_uuid);


--
-- Name: v_call_block_extension_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_block_extension_uuid_fkey ON public.v_call_block USING btree (extension_uuid);


--
-- Name: v_call_broadcasts_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_broadcasts_domain_uuid_fkey ON public.v_call_broadcasts USING btree (domain_uuid);


--
-- Name: v_call_broadcasts_recording_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_broadcasts_recording_uuid_fkey ON public.v_call_broadcasts USING btree (recording_uuid);


--
-- Name: v_call_center_agents_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_center_agents_domain_uuid_fkey ON public.v_call_center_agents USING btree (domain_uuid);


--
-- Name: v_call_center_agents_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_center_agents_user_uuid_fkey ON public.v_call_center_agents USING btree (user_uuid);


--
-- Name: v_call_center_queues_dialplan_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_center_queues_dialplan_uuid_fkey ON public.v_call_center_queues USING btree (dialplan_uuid);


--
-- Name: v_call_center_queues_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_center_queues_domain_uuid_fkey ON public.v_call_center_queues USING btree (domain_uuid);


--
-- Name: v_call_center_tiers_call_center_agent_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_center_tiers_call_center_agent_uuid_fkey ON public.v_call_center_tiers USING btree (call_center_agent_uuid);


--
-- Name: v_call_center_tiers_call_center_queue_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_center_tiers_call_center_queue_uuid_fkey ON public.v_call_center_tiers USING btree (call_center_queue_uuid);


--
-- Name: v_call_center_tiers_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_center_tiers_domain_uuid_fkey ON public.v_call_center_tiers USING btree (domain_uuid);


--
-- Name: v_call_flows_dialplan_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_flows_dialplan_uuid_fkey ON public.v_call_flows USING btree (dialplan_uuid);


--
-- Name: v_call_flows_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_call_flows_domain_uuid_fkey ON public.v_call_flows USING btree (domain_uuid);


--
-- Name: v_conference_centers_dialplan_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_centers_dialplan_uuid_fkey ON public.v_conference_centers USING btree (dialplan_uuid);


--
-- Name: v_conference_centers_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_centers_domain_uuid_fkey ON public.v_conference_centers USING btree (domain_uuid);


--
-- Name: v_conference_control_details_conference_control_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_control_details_conference_control_uuid_fkey ON public.v_conference_control_details USING btree (conference_control_uuid);


--
-- Name: v_conference_profile_params_conference_profile_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_profile_params_conference_profile_uuid_fkey ON public.v_conference_profile_params USING btree (conference_profile_uuid);


--
-- Name: v_conference_room_users_conference_room_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_room_users_conference_room_uuid_fkey ON public.v_conference_room_users USING btree (conference_room_uuid);


--
-- Name: v_conference_room_users_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_room_users_domain_uuid_fkey ON public.v_conference_room_users USING btree (domain_uuid);


--
-- Name: v_conference_room_users_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_room_users_user_uuid_fkey ON public.v_conference_room_users USING btree (user_uuid);


--
-- Name: v_conference_rooms_conference_center_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_rooms_conference_center_uuid_fkey ON public.v_conference_rooms USING btree (conference_center_uuid);


--
-- Name: v_conference_rooms_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_rooms_domain_uuid_fkey ON public.v_conference_rooms USING btree (domain_uuid);


--
-- Name: v_conference_session_details_conference_session_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_session_details_conference_session_uuid_fkey ON public.v_conference_session_details USING btree (conference_session_uuid);


--
-- Name: v_conference_session_details_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_session_details_domain_uuid_fkey ON public.v_conference_session_details USING btree (domain_uuid);


--
-- Name: v_conference_session_details_meeting_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_session_details_meeting_uuid_fkey ON public.v_conference_session_details USING btree (meeting_uuid);


--
-- Name: v_conference_sessions_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_sessions_domain_uuid_fkey ON public.v_conference_sessions USING btree (domain_uuid);


--
-- Name: v_conference_sessions_meeting_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_sessions_meeting_uuid_fkey ON public.v_conference_sessions USING btree (meeting_uuid);


--
-- Name: v_conference_users_conference_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_users_conference_uuid_fkey ON public.v_conference_users USING btree (conference_uuid);


--
-- Name: v_conference_users_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_users_domain_uuid_fkey ON public.v_conference_users USING btree (domain_uuid);


--
-- Name: v_conference_users_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conference_users_user_uuid_fkey ON public.v_conference_users USING btree (user_uuid);


--
-- Name: v_conferences_dialplan_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conferences_dialplan_uuid_fkey ON public.v_conferences USING btree (dialplan_uuid);


--
-- Name: v_conferences_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_conferences_domain_uuid_fkey ON public.v_conferences USING btree (domain_uuid);


--
-- Name: v_contact_addresses_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_addresses_contact_uuid_fkey ON public.v_contact_addresses USING btree (contact_uuid);


--
-- Name: v_contact_addresses_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_addresses_domain_uuid_fkey ON public.v_contact_addresses USING btree (domain_uuid);


--
-- Name: v_contact_attachments_attachment_uploaded_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_attachments_attachment_uploaded_user_uuid_fkey ON public.v_contact_attachments USING btree (attachment_uploaded_user_uuid);


--
-- Name: v_contact_attachments_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_attachments_contact_uuid_fkey ON public.v_contact_attachments USING btree (contact_uuid);


--
-- Name: v_contact_attachments_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_attachments_domain_uuid_fkey ON public.v_contact_attachments USING btree (domain_uuid);


--
-- Name: v_contact_emails_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_emails_contact_uuid_fkey ON public.v_contact_emails USING btree (contact_uuid);


--
-- Name: v_contact_emails_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_emails_domain_uuid_fkey ON public.v_contact_emails USING btree (domain_uuid);


--
-- Name: v_contact_groups_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_groups_contact_uuid_fkey ON public.v_contact_groups USING btree (contact_uuid);


--
-- Name: v_contact_groups_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_groups_domain_uuid_fkey ON public.v_contact_groups USING btree (domain_uuid);


--
-- Name: v_contact_groups_group_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_groups_group_uuid_fkey ON public.v_contact_groups USING btree (group_uuid);


--
-- Name: v_contact_notes_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_notes_contact_uuid_fkey ON public.v_contact_notes USING btree (contact_uuid);


--
-- Name: v_contact_notes_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_notes_domain_uuid_fkey ON public.v_contact_notes USING btree (domain_uuid);


--
-- Name: v_contact_phones_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_phones_contact_uuid_fkey ON public.v_contact_phones USING btree (contact_uuid);


--
-- Name: v_contact_phones_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_phones_domain_uuid_fkey ON public.v_contact_phones USING btree (domain_uuid);


--
-- Name: v_contact_relations_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_relations_contact_uuid_fkey ON public.v_contact_relations USING btree (contact_uuid);


--
-- Name: v_contact_relations_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_relations_domain_uuid_fkey ON public.v_contact_relations USING btree (domain_uuid);


--
-- Name: v_contact_relations_relation_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_relations_relation_contact_uuid_fkey ON public.v_contact_relations USING btree (relation_contact_uuid);


--
-- Name: v_contact_settings_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_settings_contact_uuid_fkey ON public.v_contact_settings USING btree (contact_uuid);


--
-- Name: v_contact_settings_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_settings_domain_uuid_fkey ON public.v_contact_settings USING btree (domain_uuid);


--
-- Name: v_contact_times_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_times_contact_uuid_fkey ON public.v_contact_times USING btree (contact_uuid);


--
-- Name: v_contact_times_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_times_domain_uuid_fkey ON public.v_contact_times USING btree (domain_uuid);


--
-- Name: v_contact_times_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_times_user_uuid_fkey ON public.v_contact_times USING btree (user_uuid);


--
-- Name: v_contact_urls_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_urls_contact_uuid_fkey ON public.v_contact_urls USING btree (contact_uuid);


--
-- Name: v_contact_urls_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_urls_domain_uuid_fkey ON public.v_contact_urls USING btree (domain_uuid);


--
-- Name: v_contact_users_contact_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_users_contact_uuid_fkey ON public.v_contact_users USING btree (contact_uuid);


--
-- Name: v_contact_users_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_users_domain_uuid_fkey ON public.v_contact_users USING btree (domain_uuid);


--
-- Name: v_contact_users_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contact_users_user_uuid_fkey ON public.v_contact_users USING btree (user_uuid);


--
-- Name: v_contacts_contact_parent_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contacts_contact_parent_uuid_fkey ON public.v_contacts USING btree (contact_parent_uuid);


--
-- Name: v_contacts_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_contacts_domain_uuid_fkey ON public.v_contacts USING btree (domain_uuid);


--
-- Name: v_dashboard_widget_groups_dashboard_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_dashboard_widget_groups_dashboard_uuid_fkey ON public.v_dashboard_widget_groups USING btree (dashboard_uuid);


--
-- Name: v_dashboard_widget_groups_dashboard_widget_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_dashboard_widget_groups_dashboard_widget_uuid_fkey ON public.v_dashboard_widget_groups USING btree (dashboard_widget_uuid);


--
-- Name: v_dashboard_widgets_dashboard_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_dashboard_widgets_dashboard_uuid_fkey ON public.v_dashboard_widgets USING btree (dashboard_uuid);


--
-- Name: v_dashboard_widgets_dashboard_widget_parent_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_dashboard_widgets_dashboard_widget_parent_uuid_fkey ON public.v_dashboard_widgets USING btree (dashboard_widget_parent_uuid);


--
-- Name: v_dashboards_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_dashboards_domain_uuid_fkey ON public.v_dashboards USING btree (domain_uuid);


--
-- Name: v_database_transactions_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_database_transactions_domain_uuid_fkey ON public.v_database_transactions USING btree (domain_uuid);


--
-- Name: v_destinations_dialplan_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_destinations_dialplan_uuid_fkey ON public.v_destinations USING btree (dialplan_uuid);


--
-- Name: v_destinations_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_destinations_domain_uuid_fkey ON public.v_destinations USING btree (domain_uuid);


--
-- Name: v_destinations_fax_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_destinations_fax_uuid_fkey ON public.v_destinations USING btree (fax_uuid);


--
-- Name: v_destinations_group_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_destinations_group_uuid_fkey ON public.v_destinations USING btree (group_uuid);


--
-- Name: v_destinations_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_destinations_user_uuid_fkey ON public.v_destinations USING btree (user_uuid);


--
-- Name: v_device_keys_device_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_keys_device_uuid_fkey ON public.v_device_keys USING btree (device_uuid);


--
-- Name: v_device_keys_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_keys_domain_uuid_fkey ON public.v_device_keys USING btree (domain_uuid);


--
-- Name: v_device_lines_device_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_lines_device_uuid_fkey ON public.v_device_lines USING btree (device_uuid);


--
-- Name: v_device_lines_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_lines_domain_uuid_fkey ON public.v_device_lines USING btree (domain_uuid);


--
-- Name: v_device_profile_keys_device_profile_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_profile_keys_device_profile_uuid_fkey ON public.v_device_profile_keys USING btree (device_profile_uuid);


--
-- Name: v_device_profile_keys_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_profile_keys_domain_uuid_fkey ON public.v_device_profile_keys USING btree (domain_uuid);


--
-- Name: v_device_profile_settings_device_profile_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_profile_settings_device_profile_uuid_fkey ON public.v_device_profile_settings USING btree (device_profile_uuid);


--
-- Name: v_device_profile_settings_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_profile_settings_domain_uuid_fkey ON public.v_device_profile_settings USING btree (domain_uuid);


--
-- Name: v_device_profiles_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_profiles_domain_uuid_fkey ON public.v_device_profiles USING btree (domain_uuid);


--
-- Name: v_device_settings_device_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_settings_device_uuid_fkey ON public.v_device_settings USING btree (device_uuid);


--
-- Name: v_device_settings_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_settings_domain_uuid_fkey ON public.v_device_settings USING btree (domain_uuid);


--
-- Name: v_device_vendor_function_groups_device_vendor_function_uuid_fke; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_vendor_function_groups_device_vendor_function_uuid_fke ON public.v_device_vendor_function_groups USING btree (device_vendor_function_uuid);


--
-- Name: v_device_vendor_function_groups_device_vendor_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_vendor_function_groups_device_vendor_uuid_fkey ON public.v_device_vendor_function_groups USING btree (device_vendor_uuid);


--
-- Name: v_device_vendor_function_groups_group_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_vendor_function_groups_group_uuid_fkey ON public.v_device_vendor_function_groups USING btree (group_uuid);


--
-- Name: v_device_vendor_functions_device_vendor_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_device_vendor_functions_device_vendor_uuid_fkey ON public.v_device_vendor_functions USING btree (device_vendor_uuid);


--
-- Name: v_devices_device_profile_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_devices_device_profile_uuid_fkey ON public.v_devices USING btree (device_profile_uuid);


--
-- Name: v_devices_device_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_devices_device_user_uuid_fkey ON public.v_devices USING btree (device_user_uuid);


--
-- Name: v_devices_device_uuid_alternate_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_devices_device_uuid_alternate_fkey ON public.v_devices USING btree (device_uuid_alternate);


--
-- Name: v_devices_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_devices_domain_uuid_fkey ON public.v_devices USING btree (domain_uuid);


--
-- Name: v_dialplan_details_dialplan_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_dialplan_details_dialplan_uuid_fkey ON public.v_dialplan_details USING btree (dialplan_uuid);


--
-- Name: v_dialplan_details_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_dialplan_details_domain_uuid_fkey ON public.v_dialplan_details USING btree (domain_uuid);


--
-- Name: v_dialplans_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_dialplans_domain_uuid_fkey ON public.v_dialplans USING btree (domain_uuid);


--
-- Name: v_domain_settings_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_domain_settings_domain_uuid_fkey ON public.v_domain_settings USING btree (domain_uuid);


--
-- Name: v_domains_domain_parent_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_domains_domain_parent_uuid_fkey ON public.v_domains USING btree (domain_parent_uuid);


--
-- Name: v_email_queue_attachments_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_email_queue_attachments_domain_uuid_fkey ON public.v_email_queue_attachments USING btree (domain_uuid);


--
-- Name: v_email_queue_attachments_email_queue_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_email_queue_attachments_email_queue_uuid_fkey ON public.v_email_queue_attachments USING btree (email_queue_uuid);


--
-- Name: v_email_queue_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_email_queue_domain_uuid_fkey ON public.v_email_queue USING btree (domain_uuid);


--
-- Name: v_email_templates_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_email_templates_domain_uuid_fkey ON public.v_email_templates USING btree (domain_uuid);


--
-- Name: v_emergency_logs_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_emergency_logs_domain_uuid_fkey ON public.v_emergency_logs USING btree (domain_uuid);


--
-- Name: v_extension_settings_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_extension_settings_domain_uuid_fkey ON public.v_extension_settings USING btree (domain_uuid);


--
-- Name: v_extension_users_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_extension_users_domain_uuid_fkey ON public.v_extension_users USING btree (domain_uuid);


--
-- Name: v_extension_users_extension_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_extension_users_extension_uuid_fkey ON public.v_extension_users USING btree (extension_uuid);


--
-- Name: v_extension_users_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_extension_users_user_uuid_fkey ON public.v_extension_users USING btree (user_uuid);


--
-- Name: v_extensions_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_extensions_domain_uuid_fkey ON public.v_extensions USING btree (domain_uuid);


--
-- Name: v_extensions_follow_me_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_extensions_follow_me_uuid_fkey ON public.v_extensions USING btree (follow_me_uuid);


--
-- Name: v_fax_dialplan_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fax_dialplan_uuid_fkey ON public.v_fax USING btree (dialplan_uuid);


--
-- Name: v_fax_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fax_domain_uuid_fkey ON public.v_fax USING btree (domain_uuid);


--
-- Name: v_fax_files_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fax_files_domain_uuid_fkey ON public.v_fax_files USING btree (domain_uuid);


--
-- Name: v_fax_logs_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fax_logs_domain_uuid_fkey ON public.v_fax_logs USING btree (domain_uuid);


--
-- Name: v_fax_logs_fax_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fax_logs_fax_uuid_fkey ON public.v_fax_logs USING btree (fax_uuid);


--
-- Name: v_fax_queue_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fax_queue_domain_uuid_fkey ON public.v_fax_queue USING btree (domain_uuid);


--
-- Name: v_fax_queue_fax_log_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fax_queue_fax_log_uuid_fkey ON public.v_fax_queue USING btree (fax_log_uuid);


--
-- Name: v_fax_users_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fax_users_domain_uuid_fkey ON public.v_fax_users USING btree (domain_uuid);


--
-- Name: v_fax_users_fax_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fax_users_fax_uuid_fkey ON public.v_fax_users USING btree (fax_uuid);


--
-- Name: v_fax_users_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fax_users_user_uuid_fkey ON public.v_fax_users USING btree (user_uuid);


--
-- Name: v_fifo_dialplan_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fifo_dialplan_uuid_fkey ON public.v_fifo USING btree (dialplan_uuid);


--
-- Name: v_fifo_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fifo_domain_uuid_fkey ON public.v_fifo USING btree (domain_uuid);


--
-- Name: v_fifo_members_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fifo_members_domain_uuid_fkey ON public.v_fifo_members USING btree (domain_uuid);


--
-- Name: v_fifo_members_fifo_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_fifo_members_fifo_uuid_fkey ON public.v_fifo_members USING btree (fifo_uuid);


--
-- Name: v_follow_me_destinations_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_follow_me_destinations_domain_uuid_fkey ON public.v_follow_me_destinations USING btree (domain_uuid);


--
-- Name: v_follow_me_destinations_follow_me_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_follow_me_destinations_follow_me_uuid_fkey ON public.v_follow_me_destinations USING btree (follow_me_uuid);


--
-- Name: v_follow_me_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_follow_me_domain_uuid_fkey ON public.v_follow_me USING btree (domain_uuid);


--
-- Name: v_gateways_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_gateways_domain_uuid_fkey ON public.v_gateways USING btree (domain_uuid);


--
-- Name: v_group_permissions_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_group_permissions_domain_uuid_fkey ON public.v_group_permissions USING btree (domain_uuid);


--
-- Name: v_group_permissions_group_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_group_permissions_group_uuid_fkey ON public.v_group_permissions USING btree (group_uuid);


--
-- Name: v_groups_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_groups_domain_uuid_fkey ON public.v_groups USING btree (domain_uuid);


--
-- Name: v_ivr_menu_options_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ivr_menu_options_domain_uuid_fkey ON public.v_ivr_menu_options USING btree (domain_uuid);


--
-- Name: v_ivr_menu_options_ivr_menu_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ivr_menu_options_ivr_menu_uuid_fkey ON public.v_ivr_menu_options USING btree (ivr_menu_uuid);


--
-- Name: v_ivr_menus_dialplan_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ivr_menus_dialplan_uuid_fkey ON public.v_ivr_menus USING btree (dialplan_uuid);


--
-- Name: v_ivr_menus_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ivr_menus_domain_uuid_fkey ON public.v_ivr_menus USING btree (domain_uuid);


--
-- Name: v_menu_item_groups_group_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_menu_item_groups_group_uuid_fkey ON public.v_menu_item_groups USING btree (group_uuid);


--
-- Name: v_menu_item_groups_menu_item_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_menu_item_groups_menu_item_uuid_fkey ON public.v_menu_item_groups USING btree (menu_item_uuid);


--
-- Name: v_menu_item_groups_menu_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_menu_item_groups_menu_uuid_fkey ON public.v_menu_item_groups USING btree (menu_uuid);


--
-- Name: v_menu_items_menu_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_menu_items_menu_uuid_fkey ON public.v_menu_items USING btree (menu_uuid);


--
-- Name: v_menu_languages_menu_item_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_menu_languages_menu_item_uuid_fkey ON public.v_menu_languages USING btree (menu_item_uuid);


--
-- Name: v_menu_languages_menu_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_menu_languages_menu_uuid_fkey ON public.v_menu_languages USING btree (menu_uuid);


--
-- Name: v_music_on_hold_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_music_on_hold_domain_uuid_fkey ON public.v_music_on_hold USING btree (domain_uuid);


--
-- Name: v_number_translation_details_number_translation_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_number_translation_details_number_translation_uuid_fkey ON public.v_number_translation_details USING btree (number_translation_uuid);


--
-- Name: v_phrase_details_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_phrase_details_domain_uuid_fkey ON public.v_phrase_details USING btree (domain_uuid);


--
-- Name: v_phrase_details_phrase_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_phrase_details_phrase_uuid_fkey ON public.v_phrase_details USING btree (phrase_uuid);


--
-- Name: v_phrases_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_phrases_domain_uuid_fkey ON public.v_phrases USING btree (domain_uuid);


--
-- Name: v_pin_numbers_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_pin_numbers_domain_uuid_fkey ON public.v_pin_numbers USING btree (domain_uuid);


--
-- Name: v_recordings_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_recordings_domain_uuid_fkey ON public.v_recordings USING btree (domain_uuid);


--
-- Name: v_ring_group_destinations_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ring_group_destinations_domain_uuid_fkey ON public.v_ring_group_destinations USING btree (domain_uuid);


--
-- Name: v_ring_group_destinations_ring_group_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ring_group_destinations_ring_group_uuid_fkey ON public.v_ring_group_destinations USING btree (ring_group_uuid);


--
-- Name: v_ring_group_users_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ring_group_users_domain_uuid_fkey ON public.v_ring_group_users USING btree (domain_uuid);


--
-- Name: v_ring_group_users_ring_group_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ring_group_users_ring_group_uuid_fkey ON public.v_ring_group_users USING btree (ring_group_uuid);


--
-- Name: v_ring_group_users_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ring_group_users_user_uuid_fkey ON public.v_ring_group_users USING btree (user_uuid);


--
-- Name: v_ring_groups_dialplan_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ring_groups_dialplan_uuid_fkey ON public.v_ring_groups USING btree (dialplan_uuid);


--
-- Name: v_ring_groups_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_ring_groups_domain_uuid_fkey ON public.v_ring_groups USING btree (domain_uuid);


--
-- Name: v_sip_profile_domains_sip_profile_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_sip_profile_domains_sip_profile_uuid_fkey ON public.v_sip_profile_domains USING btree (sip_profile_uuid);


--
-- Name: v_sip_profile_settings_sip_profile_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_sip_profile_settings_sip_profile_uuid_fkey ON public.v_sip_profile_settings USING btree (sip_profile_uuid);


--
-- Name: v_streams_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_streams_domain_uuid_fkey ON public.v_streams USING btree (domain_uuid);


--
-- Name: v_user_groups_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_user_groups_domain_uuid_fkey ON public.v_user_groups USING btree (domain_uuid);


--
-- Name: v_user_groups_group_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_user_groups_group_uuid_fkey ON public.v_user_groups USING btree (group_uuid);


--
-- Name: v_user_groups_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_user_groups_user_uuid_fkey ON public.v_user_groups USING btree (user_uuid);


--
-- Name: v_user_logs_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_user_logs_domain_uuid_fkey ON public.v_user_logs USING btree (domain_uuid);


--
-- Name: v_user_settings_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_user_settings_domain_uuid_fkey ON public.v_user_settings USING btree (domain_uuid);


--
-- Name: v_user_settings_user_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_user_settings_user_uuid_fkey ON public.v_user_settings USING btree (user_uuid);


--
-- Name: v_users_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_users_domain_uuid_fkey ON public.v_users USING btree (domain_uuid);


--
-- Name: v_voicemail_destinations_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_voicemail_destinations_domain_uuid_fkey ON public.v_voicemail_destinations USING btree (domain_uuid);


--
-- Name: v_voicemail_destinations_voicemail_uuid_copy_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_voicemail_destinations_voicemail_uuid_copy_fkey ON public.v_voicemail_destinations USING btree (voicemail_uuid_copy);


--
-- Name: v_voicemail_destinations_voicemail_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_voicemail_destinations_voicemail_uuid_fkey ON public.v_voicemail_destinations USING btree (voicemail_uuid);


--
-- Name: v_voicemail_greetings_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_voicemail_greetings_domain_uuid_fkey ON public.v_voicemail_greetings USING btree (domain_uuid);


--
-- Name: v_voicemail_messages_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_voicemail_messages_domain_uuid_fkey ON public.v_voicemail_messages USING btree (domain_uuid);


--
-- Name: v_voicemail_messages_voicemail_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_voicemail_messages_voicemail_uuid_fkey ON public.v_voicemail_messages USING btree (voicemail_uuid);


--
-- Name: v_voicemail_options_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_voicemail_options_domain_uuid_fkey ON public.v_voicemail_options USING btree (domain_uuid);


--
-- Name: v_voicemail_options_voicemail_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_voicemail_options_voicemail_uuid_fkey ON public.v_voicemail_options USING btree (voicemail_uuid);


--
-- Name: v_voicemails_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_voicemails_domain_uuid_fkey ON public.v_voicemails USING btree (domain_uuid);


--
-- Name: v_xml_cdr_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_domain_uuid_fkey ON public.v_xml_cdr USING btree (domain_uuid);


--
-- Name: v_xml_cdr_extensions_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_extensions_domain_uuid_fkey ON public.v_xml_cdr_extensions USING btree (domain_uuid);


--
-- Name: v_xml_cdr_extensions_xml_cdr_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_extensions_xml_cdr_uuid_fkey ON public.v_xml_cdr_extensions USING btree (xml_cdr_uuid);


--
-- Name: v_xml_cdr_flow_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_flow_domain_uuid_fkey ON public.v_xml_cdr_flow USING btree (domain_uuid);


--
-- Name: v_xml_cdr_flow_xml_cdr_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_flow_xml_cdr_uuid_fkey ON public.v_xml_cdr_flow USING btree (xml_cdr_uuid);


--
-- Name: v_xml_cdr_json_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_json_domain_uuid_fkey ON public.v_xml_cdr_json USING btree (domain_uuid);


--
-- Name: v_xml_cdr_json_xml_cdr_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_json_xml_cdr_uuid_fkey ON public.v_xml_cdr_json USING btree (xml_cdr_uuid);


--
-- Name: v_xml_cdr_logs_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_logs_domain_uuid_fkey ON public.v_xml_cdr_logs USING btree (domain_uuid);


--
-- Name: v_xml_cdr_logs_xml_cdr_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_logs_xml_cdr_uuid_fkey ON public.v_xml_cdr_logs USING btree (xml_cdr_uuid);


--
-- Name: v_xml_cdr_provider_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_provider_uuid_fkey ON public.v_xml_cdr USING btree (provider_uuid);


--
-- Name: v_xml_cdr_transcripts_domain_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_transcripts_domain_uuid_fkey ON public.v_xml_cdr_transcripts USING btree (domain_uuid);


--
-- Name: v_xml_cdr_transcripts_xml_cdr_uuid_fkey; Type: INDEX; Schema: public; Owner: fusionpbx
--

CREATE INDEX v_xml_cdr_transcripts_xml_cdr_uuid_fkey ON public.v_xml_cdr_transcripts USING btree (xml_cdr_uuid);


--
-- PostgreSQL database dump complete
--

\unrestrict YggB0urmHh9PskZ2naG1eMOWcVuwogrcgPszqoupeogc9sEy8jZpOvDJpPsHpUF

