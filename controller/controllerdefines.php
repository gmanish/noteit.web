<?php
	
	class Command
	{
		public static $tag			= 'command';
		
		public static $arg1			= 'arg1';
		public static $arg2			= 'arg2';
		public static $arg3			= 'arg3';
        public static $arg4         = 'arg4';
        public static $arg5         = 'arg5';
        public static $arg6         = 'arg6';
        public static $arg7         = 'arg7';
		public static $arg8			= 'arg8';
		public static $arg9			= 'arg9';
		public static $arg10		= 'arg10';
		public static $arg11		= 'arg11';
	}

	class Handler
	{
		public static $do_get_countries		= 'do_get_countries';
		public static $do_get_currencies	= 'do_get_currencies';
		public static $do_get_units			= 'do_get_units';
		
		public static $do_register 			= 'do_register';
		public static $do_login				= 'do_login';

		public static $do_save_prefs		= 'do_save_prefs';
		public static $do_change_password	= 'do_change_password';
		
        // These functions do not redirect output or generate html.
        // They simply return the results as json encoded strings.

        public static $do_login_json        = 'do_login_json';

        public static $do_get_shop_list     = 'do_get_shop_list';
        public static $do_add_shop_list 	= 'do_add_shop_list';
		public static $do_delete_shop_list 	= 'do_delete_shop_list';
		public static $do_edit_shop_list	= 'do_edit_shop_list';

        public static $do_get_categories    = 'do_get_categories';
        public static $do_get_category      = 'do_get_category';
        public static $do_add_category      = 'do_add_category';
        public static $do_delete_category   = 'do_delete_category';
		public static $do_edit_category		= 'do_edit_category';
		public static $do_reorder_category	= 'do_reorder_category';

        public static $do_list_shop_items   = 'do_list_shop_items';
        public static $do_get_shop_item     = 'do_get_shop_item';
        public static $do_add_shop_item     = 'do_add_shop_item';
		public static $do_edit_shop_item	= 'do_edit_shop_item';
		public static $do_delete_item       = 'do_delete_item';
		public static $do_suggest_items		= 'do_suggest_items';
		public static $do_copy_item			= 'do_copy_item';
		public static $do_mark_all_done		= 'do_mark_all_done';
		public static $do_get_pending_cost	= 'do_get_pending_cost';
		public static $do_get_instances		= 'do_get_instances';
		public static $do_search_barcode	= 'do_search_barcode';
		
		public static $do_category_report	= 'do_category_report';
		public static $do_item_report		= 'do_item_report';
	}
	
	class HandlerExitStatus
	{
		const kCommandStatus_OK				= 0;
		const kCommandStatus_Error 			= 1;
		const kCommandStatus_Information	= 2;
	}

	class Views
	{
		const kView_Home 		= 0;
		const kView_Dashboard 	= 2;
	}
	
	/* A view is that part of the screen that it displayed between the standard 
	   header (noteitheader.tphp) and the standard footer (noteitfooter.tphp) */
	static $view_map = array(
		Views::kView_Home 		=> '../view/home.tphp',
		Views::kView_Dashboard	=> '../view/dashboard.tphp');
			
	class JSONCodes
	{
		const kRetVal 		= 'JSONRetVal'; 		// One of HandlerExitStatus
		const kRetMessage	= 'JSONRetMessage';		// string
	}
	
?>