CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "phone" varchar,
  "age" integer,
  "gender" varchar check("gender" in('male', 'female', 'other')),
  "address" text,
  "qr_code" varchar,
  "rfid_card" varchar,
  "status" varchar check("status" in('active', 'inactive')) not null default 'active'
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "permissions_name_guard_name_unique" on "permissions"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "roles_name_guard_name_unique" on "roles"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "model_has_permissions"(
  "permission_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  primary key("permission_id", "model_id", "model_type")
);
CREATE INDEX "model_has_permissions_model_id_model_type_index" on "model_has_permissions"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "model_has_roles"(
  "role_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("role_id", "model_id", "model_type")
);
CREATE INDEX "model_has_roles_model_id_model_type_index" on "model_has_roles"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "role_has_permissions"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("permission_id", "role_id")
);
CREATE UNIQUE INDEX "users_qr_code_unique" on "users"("qr_code");
CREATE UNIQUE INDEX "users_rfid_card_unique" on "users"("rfid_card");
CREATE TABLE IF NOT EXISTS "subscription_plans"(
  "id" integer primary key autoincrement not null,
  "plan_name" varchar not null,
  "description" text,
  "duration_type" varchar check("duration_type" in('trial', 'daily', 'weekly', 'monthly', 'yearly')) not null,
  "duration" integer not null,
  "price" numeric not null,
  "trial_days" integer not null default '0',
  "stripe_price_id" varchar,
  "razorpay_plan_id" varchar,
  "is_active" tinyint(1) not null default '1',
  "features" text,
  "created_at" datetime,
  "updated_at" datetime,
  "image" varchar
);
CREATE TABLE IF NOT EXISTS "subscriptions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "subscription_plan_id" integer not null,
  "gateway" varchar,
  "gateway_customer_id" varchar,
  "gateway_subscription_id" varchar,
  "status" varchar check("status" in('trialing', 'active', 'canceled', 'past_due', 'expired', 'pending')) not null default 'pending',
  "trial_end_at" datetime,
  "next_billing_at" datetime,
  "started_at" datetime,
  "canceled_at" datetime,
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("subscription_plan_id") references "subscription_plans"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "activity_logs"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "date" date not null,
  "check_in_time" time,
  "check_out_time" time,
  "workout_summary" text,
  "duration_minutes" integer,
  "calories_burned" numeric,
  "exercises_done" text,
  "performance_metrics" text,
  "checked_in_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "video_filename" varchar,
  "video_caption" varchar,
  "video_path" varchar,
  "video_size_bytes" integer,
  "activity_type" varchar check("activity_type" in('gym_checkin', 'event_reel_generation')) not null default 'gym_checkin',
  "check_in_method" varchar check("check_in_method" in('qr_code', 'rfid', 'manual', 'web')),
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("checked_in_by") references "users"("id") on delete set null
);
CREATE INDEX "activity_logs_user_id_date_index" on "activity_logs"(
  "user_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "workout_plans"(
  "id" integer primary key autoincrement not null,
  "member_id" integer not null,
  "plan_name" varchar not null,
  "description" text,
  "exercises" text,
  "duration_weeks" integer,
  "start_date" date not null,
  "end_date" date,
  "status" varchar check("status" in('active', 'completed', 'paused', 'cancelled')) not null default 'active',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "demo_video_path" varchar,
  foreign key("member_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "diet_plans"(
  "id" integer primary key autoincrement not null,
  "trainer_id" integer not null,
  "member_id" integer not null,
  "plan_name" varchar not null,
  "description" text,
  "meal_plan" text,
  "nutritional_goals" text,
  "target_calories" integer,
  "start_date" date not null,
  "end_date" date,
  "status" varchar check("status" in('active', 'completed', 'paused', 'cancelled')) not null default 'active',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("trainer_id") references "users"("id") on delete cascade,
  foreign key("member_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "payments"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "subscription_id" integer,
  "amount" numeric not null,
  "payment_method" varchar check("payment_method" in('credit_card', 'debit_card', 'upi', 'paypal', 'cash', 'bank_transfer', 'other')) not null,
  "transaction_id" varchar,
  "status" varchar check("status" in('pending', 'completed', 'failed', 'refunded')) not null default 'pending',
  "payment_details" text,
  "promotional_code" varchar,
  "discount_amount" numeric not null default '0',
  "final_amount" numeric not null,
  "paid_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("subscription_id") references "subscriptions"("id") on delete set null
);
CREATE INDEX "payments_user_id_status_index" on "payments"(
  "user_id",
  "status"
);
CREATE UNIQUE INDEX "payments_transaction_id_unique" on "payments"(
  "transaction_id"
);
CREATE TABLE IF NOT EXISTS "invoices"(
  "id" integer primary key autoincrement not null,
  "payment_id" integer not null,
  "user_id" integer not null,
  "invoice_number" varchar not null,
  "invoice_date" date not null,
  "due_date" date,
  "subtotal" numeric not null,
  "tax_amount" numeric not null default '0',
  "discount_amount" numeric not null default '0',
  "total_amount" numeric not null,
  "status" varchar check("status" in('draft', 'sent', 'paid', 'overdue', 'cancelled')) not null default 'draft',
  "items" text,
  "notes" text,
  "pdf_path" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("payment_id") references "payments"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "invoices_invoice_number_index" on "invoices"("invoice_number");
CREATE UNIQUE INDEX "invoices_invoice_number_unique" on "invoices"(
  "invoice_number"
);
CREATE TABLE IF NOT EXISTS "cms_pages"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "slug" varchar not null,
  "content" text,
  "excerpt" text,
  "featured_image" varchar,
  "meta_title" varchar,
  "meta_description" text,
  "meta_keywords" varchar,
  "category" varchar,
  "order" integer not null default '0',
  "is_active" tinyint(1) not null default '1',
  "is_featured" tinyint(1) not null default '0',
  "created_by" integer,
  "updated_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("created_by") references "users"("id") on delete set null,
  foreign key("updated_by") references "users"("id") on delete set null
);
CREATE INDEX "cms_pages_slug_is_active_index" on "cms_pages"(
  "slug",
  "is_active"
);
CREATE INDEX "cms_pages_category_index" on "cms_pages"("category");
CREATE UNIQUE INDEX "cms_pages_slug_unique" on "cms_pages"("slug");
CREATE TABLE IF NOT EXISTS "menus"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "url" varchar,
  "route" varchar,
  "icon" varchar,
  "order" integer not null default '0',
  "is_active" tinyint(1) not null default '1',
  "target" varchar not null default '_self',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "landing_page_contents"(
  "id" integer primary key autoincrement not null,
  "logo" varchar,
  "hero_background_image" varchar,
  "welcome_title" varchar not null default 'Welcome to Our Gym',
  "welcome_subtitle" text,
  "about_title" varchar not null default 'About Us',
  "about_description" text,
  "about_features" text,
  "services_title" varchar not null default 'Our Services',
  "services_description" text,
  "services" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "site_settings"(
  "id" integer primary key autoincrement not null,
  "site_title" varchar not null default 'Gym Management',
  "logo" varchar,
  "contact_email" varchar,
  "contact_mobile" varchar,
  "address" text,
  "facebook_url" varchar,
  "twitter_url" varchar,
  "instagram_url" varchar,
  "linkedin_url" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "footer_partner" varchar,
  "show_title_near_logo" tinyint(1) not null default '1'
);
CREATE TABLE IF NOT EXISTS "banners"(
  "id" integer primary key autoincrement not null,
  "title" varchar,
  "subtitle" text,
  "image" varchar,
  "link" varchar,
  "link_text" varchar default 'Learn More',
  "overlay_color" varchar default '#000000',
  "overlay_opacity" numeric default '0.5',
  "order" integer not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "payment_settings"(
  "id" integer primary key autoincrement not null,
  "enable_stripe" tinyint(1) not null default '0',
  "stripe_publishable_key" text,
  "stripe_secret_key" text,
  "enable_razorpay" tinyint(1) not null default '0',
  "razorpay_key_id" text,
  "razorpay_key_secret" text,
  "enable_gpay" tinyint(1) not null default '0',
  "gpay_upi_id" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "workout_videos"(
  "id" integer primary key autoincrement not null,
  "workout_plan_id" integer not null,
  "user_id" integer not null,
  "exercise_name" varchar not null,
  "video_path" varchar not null,
  "duration_seconds" integer not null default '60',
  "status" varchar check("status" in('pending', 'approved', 'rejected')) not null default 'pending',
  "review_feedback" text,
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workout_plan_id") references "workout_plans"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE INDEX "workout_videos_workout_plan_id_user_id_index" on "workout_videos"(
  "workout_plan_id",
  "user_id"
);
CREATE INDEX "workout_videos_status_index" on "workout_videos"("status");
CREATE TABLE IF NOT EXISTS "expenses"(
  "id" integer primary key autoincrement not null,
  "category" varchar not null,
  "vendor" varchar,
  "amount" numeric not null,
  "spent_at" date not null,
  "payment_method" varchar,
  "reference" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "reference_document_path" varchar
);
CREATE INDEX "expenses_category_spent_at_index" on "expenses"(
  "category",
  "spent_at"
);
CREATE TABLE IF NOT EXISTS "incomes"(
  "id" integer primary key autoincrement not null,
  "category" varchar not null,
  "source" varchar,
  "amount" numeric not null,
  "received_at" date not null,
  "payment_method" varchar,
  "reference" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "reference_document_path" varchar
);
CREATE INDEX "incomes_category_received_at_index" on "incomes"(
  "category",
  "received_at"
);
CREATE TABLE IF NOT EXISTS "exports"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "export_type" varchar not null,
  "filename" varchar,
  "filepath" varchar,
  "format" varchar not null default 'csv',
  "filters" text,
  "status" varchar not null default 'pending',
  "error_message" text,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "exports_user_id_status_index" on "exports"("user_id", "status");
CREATE INDEX "exports_export_type_status_index" on "exports"(
  "export_type",
  "status"
);
CREATE TABLE IF NOT EXISTS "announcements"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "body" text not null,
  "audience_type" varchar check("audience_type" in('all', 'trainer', 'member')) not null default 'all',
  "status" varchar check("status" in('draft', 'published', 'archived')) not null default 'draft',
  "published_at" datetime,
  "expires_at" datetime,
  "created_by" integer not null,
  "updated_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("created_by") references "users"("id") on delete cascade,
  foreign key("updated_by") references "users"("id") on delete set null
);
CREATE INDEX "announcements_audience_type_status_index" on "announcements"(
  "audience_type",
  "status"
);
CREATE INDEX "announcements_published_at_index" on "announcements"(
  "published_at"
);
CREATE TABLE IF NOT EXISTS "in_app_notifications"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "message" text not null,
  "audience_type" varchar check("audience_type" in('all', 'trainer', 'member', 'user')) not null default 'all',
  "target_user_id" integer,
  "status" varchar check("status" in('draft', 'scheduled', 'published', 'archived')) not null default 'draft',
  "scheduled_for" datetime,
  "published_at" datetime,
  "expires_at" datetime,
  "requires_acknowledgement" tinyint(1) not null default '0',
  "created_by" integer not null,
  "updated_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("target_user_id") references "users"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete cascade,
  foreign key("updated_by") references "users"("id") on delete set null
);
CREATE INDEX "in_app_notifications_audience_type_status_index" on "in_app_notifications"(
  "audience_type",
  "status"
);
CREATE INDEX "in_app_notifications_published_at_index" on "in_app_notifications"(
  "published_at"
);
CREATE TABLE IF NOT EXISTS "notification_user"(
  "id" integer primary key autoincrement not null,
  "in_app_notification_id" integer not null,
  "user_id" integer not null,
  "read_at" datetime,
  "dismissed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("in_app_notification_id") references "in_app_notifications"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "notification_user_unique" on "notification_user"(
  "in_app_notification_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "oauth_auth_codes"(
  "id" varchar not null,
  "user_id" integer not null,
  "client_id" integer not null,
  "scopes" text,
  "revoked" tinyint(1) not null,
  "expires_at" datetime,
  primary key("id")
);
CREATE INDEX "oauth_auth_codes_user_id_index" on "oauth_auth_codes"("user_id");
CREATE TABLE IF NOT EXISTS "oauth_access_tokens"(
  "id" varchar not null,
  "user_id" integer,
  "client_id" integer not null,
  "name" varchar,
  "scopes" text,
  "revoked" tinyint(1) not null,
  "created_at" datetime,
  "updated_at" datetime,
  "expires_at" datetime,
  primary key("id")
);
CREATE INDEX "oauth_access_tokens_user_id_index" on "oauth_access_tokens"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "oauth_refresh_tokens"(
  "id" varchar not null,
  "access_token_id" varchar not null,
  "revoked" tinyint(1) not null,
  "expires_at" datetime,
  primary key("id")
);
CREATE INDEX "oauth_refresh_tokens_access_token_id_index" on "oauth_refresh_tokens"(
  "access_token_id"
);
CREATE TABLE IF NOT EXISTS "oauth_clients"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "name" varchar not null,
  "secret" varchar,
  "provider" varchar,
  "redirect" text not null,
  "personal_access_client" tinyint(1) not null,
  "password_client" tinyint(1) not null,
  "revoked" tinyint(1) not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "oauth_clients_user_id_index" on "oauth_clients"("user_id");
CREATE TABLE IF NOT EXISTS "oauth_personal_access_clients"(
  "id" integer primary key autoincrement not null,
  "client_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "activity_logs_activity_type_user_id_index" on "activity_logs"(
  "activity_type",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "cms_contents"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "key" varchar not null,
  "type" varchar not null,
  "content" text,
  "description" text,
  "image" varchar,
  "link" varchar,
  "link_text" varchar,
  "extra_data" text,
  "order" integer not null default('0'),
  "is_active" tinyint(1) not null default('1'),
  "created_by" integer,
  "updated_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "background_image" varchar,
  "video_path" varchar,
  "background_video" varchar,
  "video_is_background" tinyint(1) not null default '0',
  foreign key("updated_by") references users("id") on delete set null on update no action,
  foreign key("created_by") references users("id") on delete set null on update no action
);
CREATE INDEX "cms_contents_key_index" on "cms_contents"("key");
CREATE UNIQUE INDEX "cms_contents_key_unique" on "cms_contents"("key");
CREATE INDEX "cms_contents_type_is_active_index" on "cms_contents"(
  "type",
  "is_active"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_01_15_000001_update_subscriptions_table_for_payment_gateways',1);
INSERT INTO migrations VALUES(5,'2025_01_15_000002_add_trial_days_to_subscription_plans',1);
INSERT INTO migrations VALUES(6,'2025_11_13_192913_create_permission_tables',1);
INSERT INTO migrations VALUES(7,'2025_11_13_192933_add_fields_to_users_table',1);
INSERT INTO migrations VALUES(8,'2025_11_13_192941_create_subscription_plans_table',1);
INSERT INTO migrations VALUES(9,'2025_11_13_192948_create_subscriptions_table',1);
INSERT INTO migrations VALUES(10,'2025_11_13_192956_create_activity_logs_table',1);
INSERT INTO migrations VALUES(11,'2025_11_13_193004_create_workout_plans_table',1);
INSERT INTO migrations VALUES(12,'2025_11_13_193012_create_diet_plans_table',1);
INSERT INTO migrations VALUES(13,'2025_11_13_193021_create_payments_table',1);
INSERT INTO migrations VALUES(14,'2025_11_13_193039_create_invoices_table',1);
INSERT INTO migrations VALUES(15,'2025_11_13_195216_create_cms_pages_table',1);
INSERT INTO migrations VALUES(16,'2025_11_13_195225_create_cms_contents_table',1);
INSERT INTO migrations VALUES(17,'2025_11_13_201800_create_menus_table',1);
INSERT INTO migrations VALUES(18,'2025_11_13_205312_create_landing_page_contents_table',1);
INSERT INTO migrations VALUES(19,'2025_11_14_043042_create_site_settings_table',1);
INSERT INTO migrations VALUES(20,'2025_11_14_044842_create_banners_table',1);
INSERT INTO migrations VALUES(21,'2025_11_14_174229_add_trial_to_subscription_plans_duration_type',1);
INSERT INTO migrations VALUES(22,'2025_11_14_175126_add_image_to_subscription_plans_table',1);
INSERT INTO migrations VALUES(23,'2025_11_14_183514_create_payment_settings_table',1);
INSERT INTO migrations VALUES(24,'2025_11_15_181809_add_background_image_to_cms_contents_table',1);
INSERT INTO migrations VALUES(25,'2025_11_16_172045_create_workout_videos_table',1);
INSERT INTO migrations VALUES(26,'2025_11_16_172903_add_demo_video_path_to_workout_plans_table',1);
INSERT INTO migrations VALUES(27,'2025_11_17_090000_create_expenses_table',1);
INSERT INTO migrations VALUES(28,'2025_11_17_124947_add_video_path_to_cms_contents_table',1);
INSERT INTO migrations VALUES(29,'2025_11_17_174329_create_incomes_table',1);
INSERT INTO migrations VALUES(30,'2025_11_17_182910_create_exports_table',1);
INSERT INTO migrations VALUES(31,'2025_11_17_213603_make_tenant_id_nullable_in_payments_table',1);
INSERT INTO migrations VALUES(32,'2025_11_18_000000_add_footer_partner_to_site_settings_table',1);
INSERT INTO migrations VALUES(33,'2025_11_18_120000_create_announcements_table',1);
INSERT INTO migrations VALUES(34,'2025_11_18_120100_create_in_app_notifications_table',1);
INSERT INTO migrations VALUES(35,'2025_11_18_120200_create_notification_user_table',1);
INSERT INTO migrations VALUES(36,'2025_11_19_100000_add_reference_document_path_to_expenses_table',1);
INSERT INTO migrations VALUES(37,'2025_11_19_100100_add_reference_document_path_to_incomes_table',1);
INSERT INTO migrations VALUES(38,'2025_11_19_134134_add_is_active_to_users_table',1);
INSERT INTO migrations VALUES(39,'2025_11_20_095210_create_oauth_auth_codes_table',1);
INSERT INTO migrations VALUES(40,'2025_11_20_095211_create_oauth_access_tokens_table',1);
INSERT INTO migrations VALUES(41,'2025_11_20_095212_create_oauth_refresh_tokens_table',1);
INSERT INTO migrations VALUES(42,'2025_11_20_095213_create_oauth_clients_table',1);
INSERT INTO migrations VALUES(43,'2025_11_20_095214_create_oauth_personal_access_clients_table',1);
INSERT INTO migrations VALUES(44,'2025_11_26_160052_add_activity_type_to_activity_logs_table',2);
INSERT INTO migrations VALUES(45,'2025_11_26_160445_update_activity_type_enum_in_activity_logs_table',3);
INSERT INTO migrations VALUES(46,'2025_11_26_160737_add_web_to_check_in_method_enum_in_activity_logs_table',4);
INSERT INTO migrations VALUES(47,'2025_11_27_084024_add_show_title_near_logo_to_site_settings_table',5);
INSERT INTO migrations VALUES(48,'2025_11_27_102956_add_background_video_to_cms_contents_table',6);
INSERT INTO migrations VALUES(49,'2025_11_27_104419_ensure_background_video_column_exists',7);
INSERT INTO migrations VALUES(50,'2025_11_27_104624_fix_background_video_column_completely',8);
INSERT INTO migrations VALUES(51,'2025_11_27_124128_add_show_title_near_logo_to_site_settings_table',9);
INSERT INTO migrations VALUES(52,'2025_11_27_125043_add_video_is_background_to_cms_contents_table',10);
