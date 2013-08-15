<?php
	
	function build_top_menu() {
	//<ul class="pureCssMenu pureCssMenum0">
	//	<li class="pureCssMenui0"><a class="pureCssMenui0" href="#">HOME</a></li>
	//	<li class="pureCssMenui0"><a class="pureCssMenui0" href="#"><span>PRODUK</span></a>
	//		<ul class="pureCssMenum">
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//			<li class="pureCssMenui"><a class="pureCssMenui" href="#">Ayam Goreng</a></li>
	//		</ul>						
	//	</li>
	//	<li class="pureCssMenui0"><a class="pureCssMenui0" href="#">PELUANG USAHA</a></li>
	//	<li class="pureCssMenui0"><a class="pureCssMenui0" href="#">PEMAKAIAN</a></li>
	//	<li class="pureCssMenui0"><a class="pureCssMenui0" href="#">RESEP</a></li>
	//	<li class="pureCssMenui0"><a class="pureCssMenui0" href="#">TESTIMONIAL</a></li>
	//	<li class="pureCssMenui0"><a class="pureCssMenui0" href="#">FAQ</a></li>
	//	<li class="pureCssMenui0"><a class="pureCssMenui0" href="#">HUBUNGI KAMI</a></li>
	// </ul>
		build_menu_3(2 , 'pureCssMenu pureCssMenum0' , 'pureCssMenui0');
	}
	
	function build_prod_cat() {
		build_product_category(0);
	}
	
	function build_left_menu() {
		//<ul id=leftnavicontent>
		//	<li><a href="">TENTANG KAMI</a></li>
		//	<li><a href="">BERITA TERKINI</a></li>
		//	<li><a href="">CARI LOKASI</a></li>
		//</ul>		
		//build_menu_2(1);
		build_menu_2(4 , 'leftnavicontent');
	}
	
	function build_bottom_menu() {
		//<a href="">Home</a> &nbsp;|&nbsp; 
		//<a href="">Produk</a> &nbsp;|&nbsp; 
		//<a href="">Peluang Usaha</a> &nbsp;|&nbsp; 
		//<a href="">Pemakaian</a> &nbsp;|&nbsp; 
		//<a href="">Resep</a> &nbsp;|&nbsp; 
		//<a href="">Pertanyaan (FAQ)</a> &nbsp;|&nbsp; 
		//<a href="">Tentang Kami</a> &nbsp;|&nbsp; 
		//<a href="">Hubungi Kami</a>		
		build_menu_1(5 , 'nav');
	}
	
	function build_site_map() {
		build_menu_1(2 , 'site_map');		
	}
	
	function build_menu_1($mp_id = 0 , $ul_id = '' , $parent_id = 0) {
		$CI =& get_instance();
		$res = $CI->db->
			where('mp_id' , $mp_id)->
			where('m_parent_id' , $parent_id)->
			where('m_status' , 'Active')->
			order_by('m_id' , 'asc')->
			get('menu');
		
		foreach($res->result() as $row) {
			$stack[] = "<a href='{$row->m_link}'>{$row->m_name}</a>";
		}
		
		if ($stack) echo implode(" &nbsp;|&nbsp; " , $stack);
	}
	
	function build_product_category($pc_id = 0) {
		$CI =& get_instance();

		$sql = "
			SELECT * FROM product_category pc
			WHERE
				pc.pc_parent_id = ?
				AND pc.pc_status = 'Active'
			ORDER BY
				pc.pc_name
		";
		$res = $CI->db->query($sql , array($pc_id));

		//<ul class="cat">
		//	<li><a href="">Network Cameras</a></li>
		//	<li><a href="">Security Camera</a></li>
		//	<li><a href="">Digital Video Recorder</a></li>
		//	<li><a href="">Network Video Recorder</a></li>
		//	<li><a href="">IP Camera Software</a></li>
		//	<li><a href="">Packages</a></li>
		//	<li><a href="">Accessories</a></li>
		//	<li><a href="">Network Accessories</a></li>
		//</ul>		
		
		if ($pc_id > 0) echo "<ul class='child_cat'>";
		else echo "<ul class='cat'>";
		foreach($res->result() as $row) {
			$link = site_url('product/view/' . $row->pc_id);
			echo "<li><a href='{$link}'>{$row->pc_name}</a>";
			build_product_category($row->pc_id);
			echo "</li>";
		}
		echo "</ul>";
	}


	function build_menu_2($mp_id = 0 , $ul_id = '' , $parent_id = 0) {
		$CI =& get_instance();

		$sql = "
			SELECT m.* , COUNT(m2.m_id) AS count FROM menu m
			LEFT JOIN menu m2 ON m.m_id = m2.m_parent_id AND m2.m_status = 'Active'
			WHERE
				m.mp_id = ?
				AND m.m_parent_id = ?
				AND m.m_status = 'Active'
			GROUP BY m.m_id
		";
		$res = $CI->db->query($sql , array($mp_id , $parent_id));
		if ($res->num_rows() <= 0) return;
		
		if ($parent_id > 0) echo "<ul>";
		else echo "<ul id='$ul_id'>";
		foreach($res->result() as $row) {
			echo "<li><a href='{$row->m_link}'>{$row->m_name}</a>";
			if ($row->count > 0) echo " [<a href='#'>+</a>]";
			build_menu_2($mp_id , $ul_id , $row->m_id);
			echo "</li>";
		}
		echo "</ul>";
	}

	function build_menu_3($mp_id = 0 , $ul_id = '' , $ul_id2 = '' , $parent_id = 0) {
		$CI =& get_instance();

		$sql = "
			SELECT m.* , COUNT(m2.m_id) AS count FROM menu m
			LEFT JOIN menu m2 ON m.m_id = m2.m_parent_id AND m2.m_status = 'Active'
			WHERE
				m.mp_id = ?
				AND m.m_parent_id = ?
				AND m.m_status = 'Active'
			GROUP BY m.m_id
		";
		$res = $CI->db->query($sql , array($mp_id , $parent_id));
		if ($res->num_rows() <= 0) return;
		
		if ($parent_id > 0) echo "<ul>";
		else echo "<ul class='$ul_id'>";
		foreach($res->result() as $row) {
			echo "<li class='{$ul_id2}'><a class='{$ul_id2}' href='{$row->m_link}'>{$row->m_name}</a>";
			if ($row->count > 0) echo " [<a href='#'>+</a>]";
			build_menu_3($mp_id , $ul_id , $ul_id2 , $row->m_id);
			echo "</li>";
		}
		echo "</ul>";
	}
	
/*	function build_menu_2($mp_id = 0 , $parent_id = 0) {
		$CI =& get_instance();
		$res = $CI->db->
			where('mp_id' , $mp_id)->
			where('m_parent_id' , $parent_id)->
			where('m_status' , 'Active')->
			get('menu');
		
		foreach($res->result() as $row) {
			if ($parent_id > 0) $cl = 'navi_item_2';
			else $cl = 'navi_item';
			echo "<tr><td class=\"{$cl}\"><a href=\"{$row->m_link}\">{$row->m_name}</a></td></tr>";
			build_menu_2($mp_id , $row->m_id);
		}
	}
*/