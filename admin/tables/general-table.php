<?php
defined('ABSPATH') || die('Access Denied');

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


abstract class GCal_GeneralTable extends WP_List_Table {
	protected $bulk_delete_available = false;
	protected $columns = array();
	protected $sortable_columns = array();

	/**
	 * @var \GLCalendar\Entity null
	 */
	protected $entity = null;

	protected $prepare_condition = null;

	public function __construct( $args = array() ) {
		parent::__construct( array(
			'plural' => 'items',
			'signular' => 'item',
			'ajax' => false
		) );
		$this->prepare_items($this->prepare_condition);
	}
	private function call_ent ($func, $params=array()) {
		return call_user_func_array(array($this->entity, $func), $params);
	}
	public function prepare_items ($condition=null) {
		$this->_column_headers = array(
			$this->get_columns(),
			array('id'=>'Item Id'),
			$this->get_sortable_columns()
		);

		if ($this->bulk_delete_available and $this->current_action()=='delete' and !empty($_REQUEST['item'])) {
			if (!check_admin_referer('bulk-' . $this->_args['plural'])) {
				echo "<div class='notice notice-error is-dismissible'>
                    <p>Unable to delete entities with ids: ".implode(",",$_REQUEST['item']).". Notice error.</p>
                </div>";
			} elseif (!$this->call_ent('deleteByIds', array($_REQUEST['item']))){  // array_map intval will be called in this function
				echo "<div class='notice notice-error is-dismissible'>
                    <p>Unable to delete entities with ids: ".implode(",",$_REQUEST['item'])."</p>
                </div>";
			}
		}

		$sorting = '';
		if (in_array(@$_REQUEST['orderby'],array_keys($this->get_sortable_columns())) and
		    in_array(strtolower($_REQUEST['order']),array('asc','desc'))) {
			$sorting = "{$_REQUEST['orderby']} {$_REQUEST['order']}";
		}
		if (empty($sorting)) $sorting = "id DESC";

		$entities = $this->call_ent('getList', array($condition, $sorting));
		if (!empty($entities)){
			$total = $this->call_ent('getLastRowsCount');
			$this->set_pagination_args(array(
				'total_items' => $total,
				'total_pages' => 1,
				'per_page' => $total
			));
			$this->items=array_map(function($g){
				return (array)$g;
			},$entities);
		}
	}

	protected function get_bulk_actions() {
		if ($this->bulk_delete_available)
			return array(
				'delete' => "Delete"
			);
		return array();
	}

	public function get_columns () {
		return array_merge(array(
			'cb' => '<input type="checkbox" />'
		),$this->columns);
	}

	public function get_sortable_columns() {
		$a = array();
		foreach($this->sortable_columns as $v) {
			$a[$v] = array($v, false);
		}
		return $a;
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'item',
			$item['id']
		);
	}

	public function column_default( $item, $column_name ) {
		if ($column_name == 'name') {
			return '<a href="?page='.urlencode(esc_attr($_REQUEST['page'])).'&view_id='.$item['id'].'">'.$item[$column_name].'</a>';
		}
		return $item[$column_name];
	}
}