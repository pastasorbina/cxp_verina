/*
 * File:        jquery.dataTables.js
 * Version:     1.1
 * CVS:         $Id$
 * Description: Paginate, search and sort HTML tables
 * Author:      Allan Jardine
 * Created:     28/3/2008
 * Modified:    $Date$ by $Author$
 * Language:    Javascript
 * License:     GPL v2 or BSD 3 point style
 * Project:     Mtaala
 * Contact:     allan.jardine@sprymedia.co.uk
 * 
 * Copyright 2007-2008 Allan Jardine, all rights reserved.
 *
 * This source file is free software, under either the GPL v2 license or a
 * BSD style license, as supplied with this software.
 * 
 * This source file is distributed in the hope that it will be useful, but 
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY 
 * or FITNESS FOR A PARTICULAR PURPOSE. See the license files for details.
 * 
 * For details pleease refer to: http://sprymedia.co.uk/article/DataTables
 */


(function() {
	$.fn.dataTable = function( oInit )
	{
		/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Local variables
		 */
		
		/*
		 * Variable: _oFeatures
		 * Purpose:  Indicate the enablement of key dataTable features
		 * Scope:    jQuery.dataTable 
		 */
		var _oFeatures = {
			"bPaginate": true,
			"bLengthChange": true,
			"bFilter": true,
			"bSort": true,
			"bInfo": true,
			"bProcessing": true,
			"bAutoWidth": true
		};
		
		/*
		 * Variable: _oLanguage
		 * Purpose:  Store the language strings used by dataTables
		 * Scope:    jQuery.dataTable
		 * Notes:    The words in the format _VAR_ are variables which are dynamically replaced
		 *   by javascript
		 */
		var _oLanguage = {
			"sProcessing": "Processing...",
			"sLengthMenu": "Show _MENU_ entries",
			"sZeroRecords": "No matching records found",
			"sInfo": "Showing _START_ to _END_ of _TOTAL_ entries",
			"sInfoEmtpy": "Showing 0 to 0 of 0 entries",
			"sInfoFiltered": "(filtered from _MAX_ total entries)",
			"sInfoPostFix": "",
			"sSearch": "Search:",
			"sUrl": ""
		};			
						
		/*
		 * Variable: _aoColumns
		 * Purpose:  Store information about each column that is in use
		 * Scope:    jQuery.dataTable 
		 */
		var _aoColumns = new Array();
		
		/*
		 * Variable: _aaData
		 * Purpose:  Data to be used for the table of information
		 * Scope:    jQuery.dataTable
		 * Notes:    (horiz) data row,  (vert) columns
		 */
		var _aaData = new Array();
		
		/*
		 * Variable: _aaDataMaster
		 * Purpose:  Complete record of original information from the DOM
		 * Scope:    jQuery.dataTable
		 */
		var _aaDataMaster = new Array();
		
		/*
		 * Variable: _asDataSearch
		 * Purpose:  Search data array for regular expression searching
		 * Scope:    jQuery.dataTable
		 */
		var _asDataSearch = new Array();
		
		/*
		 * Variable: _sPreviousSearch
		 * Purpose:  Store the previous search incase we want to force a re-search
		 *   or compare the old search to a new one
		 * Scope:    jQuery.dataTable
		 */
		var _sPreviousSearch = '';
		
		/*
		 * Variable: _nInfo
		 * Purpose:  Info display for user to see what records are displayed
		 * Scope:    jQuery.dataTable
		 */
		var _nInfo = null;
		
		/*
		 * Variable: _nProcessing
		 * Purpose:  Processing indicator div
		 * Scope:    jQuery.dataTable
		 */
		var _nProcessing = null;
		
		/*
		 * Variable: _iDisplayLength, _iDisplayStart, _iDisplayEnd
		 * Purpose:  Display length variables
		 * Scope:    jQuery.dataTable
		 */
		var _iDisplayLength = 10;
		var _iDisplayStart = 0;
		var _iDisplayEnd = 10;
		
		/*
		 * Variable: _iColumnSorting
		 * Purpose:  Column sort index
		 * Scope:    jQuery.dataTable
		 */
		var _iColumnSorting = null;
		
		/*
		 * Variable: _iSortingDirection
		 * Purpose:  Column sort direction - 0 as per Array.sort. 1 reversed
		 * Scope:    jQuery.dataTable
		 */
		var _iSortingDirection = 0;
		
		/*
		 * Variable: _asStripClasses
		 * Purpose:  Classes to use for the striping of a table
		 * Scope:    jQuery.dataTable
		 */
		var _asStripClasses = new Array();
		
		/*
		 * Variable: _fnRowCallback
		 * Purpose:  Call this function every time a row is inserted
		 * Scope:    jQuery.dataTable
		 */
		var _fnRowCallback = null;
		
		/*
		 * Variable: _bRowOpen
		 * Purpose:  Indicate if there is a row currently 'open'
		 * Scope:    jQuery.dataTable
		 */
		var _bRowOpen = false;
		
		/*
		 * Variable: _sTableId
		 * Purpose:  Cache the table ID for quick access
		 * Scope:    jQuery.dataTable
		 */
		var _sTableId = "";
		
		/*
		 * Variable: _nTable
		 * Purpose:  Cache the table node for quick access
		 * Scope:    jQuery.dataTable
		 */
		var _nTable = null;
		
		/*
		 * Variable: _iDefaultSortIndex
		 * Purpose:  Sorting index which will be used by default
		 * Scope:    jQuery.dataTable
		 */
		var _iDefaultSortIndex = 0;
		
		/*
		 * Variable: _bInitialised
		 * Purpose:  Indicate if all required information has been read in
		 * Scope:    jQuery.dataTable
		 */
		var _bInitialised = false;
		
		
		
		
		/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * API functions
		 */
		
		/*
		 * Function: fnDraw
		 * Purpose:  Redraw the table
		 * Returns:  -
		 * Inputs:   -
		 */
		this.fnDraw = function()
		{
			_fnCalculateEnd();
			_fnDraw( this );
		}
		
		/*
		 * Function: fnFilter
		 * Purpose:  Filter the input based on data
		 * Returns:  -
		 * Inputs:   string:sInput - string to filter the table on
		 */
		this.fnFilter = function( sInput )
		{
			_fnFilter( this, sInput, 1 );
		}
		
		/*
		 * Function: fnSort
		 * Purpose:  Sort the table by a particular row
		 * Returns:  -
		 * Inputs:   int:iCol - the data index to sort on. Note that this will
		 *   not match the 'display index' if you have hidden data entries
		 */
		this.fnSort = function( iCol )
		{
				_fnSort( this, iCol );
		}
		
		/*
		 * Function: fnAddRow
		 * Purpose:  Add a new row into the table
		 * Returns:  0 - ok
		 *           1 - length error
		 * Inputs:   array:aData - the data to be added. The length must match
		 *   the original data from the DOM.
		 * Notes:    Warning - the refilter here will cause the table to redraw
		 *   starting at zero
		 */
		this.fnAddRow = function( aData )
		{
			if ( aData.length == _aoColumns.length )
			{
				_aaDataMaster[ _aaDataMaster.length++ ] = aData.slice();
				_aaData = _aaDataMaster.slice();
				
				/* Rebuild the search */
				_fnBuildSearchArray( 1 );
				
				/* Re-sort */
				_fnSort( this, _iColumnSorting, true );
				
				/* But we do need to re-filter or re-draw */
				if ( _oFeatures.bFilter )
				{
					_fnFilter( this, _sPreviousSearch );
				}
				else
				{
					_fnCalculateEnd();
					_fnDraw( this );
				}
				return 0;
			}
			else
			{
				return 1;
			}
		}
		
		/*
		 * Function: fnDeleteRow
		 * Purpose:  Remove a row for the table
		 * Returns:  array:aReturn - the row that was deleted
		 * Inputs:   int:iIndex - index of _aaData to be deleted
		 */
		this.fnDeleteRow = function( iIndexAAData, fnCallBack )
		{
			/* Check that the index's are valid */
			if ( _aaDataMaster.length == _aaData.length )
			{
				iIndexAAMaster = iIndexAAData;
			}
			else
			{
				/* Need to find the index of the master array which matches the passed index from _aaData */
				iIndexAAMaster = _fnMasterIndexFromDisplay( iIndexAAData );
			}
			
			var aReturn = _aaDataMaster[ iIndexAAMaster ].slice();
			_aaDataMaster.splice( iIndexAAMaster, 1 );
			_aaData.splice( iIndexAAData, 1 );
			
			/* Rebuild the search */
			_fnBuildSearchArray( 1 );
			
			/* If there is a user callback function - call it */
			if ( typeof fnCallBack == "function" )
			{
				fnCallBack.call( this );
			}
			
			/* Check for an 'overflow' they case for dislaying the table */
			if ( _iDisplayStart > _aaData.length )
			{
				_iDisplayStart -= _iDisplayLength;
			}
			
			_fnCalculateEnd();
			_fnDraw( this );
			
			return aReturn;
		}
		
		/*
		 * Function: fnOpen
		 * Purpose:  Open a display row (append a row after the row in question)
		 * Returns:  -
		 * Inputs:   node:nTr - the table row to 'open'
		 *           string:sHtml - the HTML to put into the row
		 *           string:sClass - class to give the new row
		 */
		this.fnOpen = function( nTr, sHtml, sClass )
		{
			/* Remove an old open row if there is one */
			this.fnClose();
			
			var nNewRow = document.createElement("tr");
			var nNewCell = document.createElement("td");
			nNewRow.appendChild( nNewCell );
			nNewRow.setAttribute( 'id', _sTableId+"_opened_row" );
			nNewRow.className = sClass;
			nNewCell.colSpan = _aoColumns.length; // XXX - does this need to be visble columns?
			nNewCell.innerHTML = sHtml;
			
			$(nNewRow).insertAfter(nTr);
			_bRowOpen = true;
		}
		
		/*
		 * Function: fnClose
		 * Purpose:  Close a display row
		 * Returns:  -
		 * Inputs:   -
		 */
		this.fnClose = function()
		{
			$('#'+_sTableId+"_opened_row").remove();
			_bRowOpen = false;
		}
		
		/*
		 * Function: fnDecrement
		 * Purpose:  Decremenet all numbers bigger than iMatch (for deleting)
		 * Returns:  -
		 * Inputs:   int:iMatch - decrement numbers bigger than this
		 *           int:iIndex - index of the data to decrement
		 */
		this.fnDecrement = function( iMatch, iIndex )
		{
			if ( typeof iIndex == 'undefined' )
				iIndex = 0;
			
			for ( var i=0 ; i<_aaDataMaster.length ; i++ )
			{
				if ( _aaDataMaster[i][iIndex]*1 > iMatch )
				{
					_aaDataMaster[i][iIndex] = (_aaDataMaster[i][iIndex]*1) - 1;
				}
			}
		}
		
		
		
		/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Local functions
		 */
		
		/*
		 * Function: _fnAddColumn
		 * Purpose:  Add a column to the list used for the table
		 * Returns:  -
		 * Inputs:   oOptions - object with sType, bVisible and bSearchable
		 * Notes:    All options in enter column can be over-ridden by the user
		 *   initialisation of dataTables
		 */
		function _fnAddColumn( oOptions )
		{
			_aoColumns[ _aoColumns.length++ ] = {
				"sType": null,
				"bVisible": true,
				"bSearchable": true,
				"bSortable": true,
				"sTitle": null,
				"sWidth": null,
				"sClass": null,
				"fnRender": null,
				"fnSort": null
			};
			
			/* User specified column options */
			if ( typeof oOptions != 'undefined' && oOptions != null )
			{
				if ( typeof oOptions.sType != 'undefined' )
					_aoColumns[ _aoColumns.length-1 ].sType = oOptions.sType;
				
				if ( typeof oOptions.bVisible != 'undefined' )
					_aoColumns[ _aoColumns.length-1 ].bVisible = oOptions.bVisible;
				
				if ( typeof oOptions.bSearchable != 'undefined' )
					_aoColumns[ _aoColumns.length-1 ].bSearchable = oOptions.bSearchable;
				
				if ( typeof oOptions.bSortable != 'undefined' )
					_aoColumns[ _aoColumns.length-1 ].bSortable = oOptions.bSortable;
				
				if ( typeof oOptions.sTitle != 'undefined' )
					_aoColumns[ _aoColumns.length-1 ].sTitle = oOptions.sTitle;
				
				if ( typeof oOptions.sWidth != 'undefined' )
					_aoColumns[ _aoColumns.length-1 ].sWidth = oOptions.sWidth;
				
				if ( typeof oOptions.sClass != 'undefined' )
					_aoColumns[ _aoColumns.length-1 ].sClass = oOptions.sClass;
				
				if ( typeof oOptions.fnRender != 'undefined' )
					_aoColumns[ _aoColumns.length-1 ].fnRender = oOptions.fnRender;
				
				if ( typeof oOptions.fnSort != 'undefined' )
					_aoColumns[ _aoColumns.length-1 ].fnSort = oOptions.fnSort;
			}
		}
		
		
		/*
		 * Function: _fnGatherData
		 * Purpose:  Read in the data from the target table
		 * Returns:  -
		 * Inputs:   object:oTable - jQuery object for the target
		 */
		function _fnGatherData( oTable )
		{
			var nDataNodes;
			
			if ( $('thead th', oTable).length != _aoColumns.length )
			{
				alert( 'Warning - columns do not match' );
			}
			
			for ( var i=0 ; i<_aoColumns.length ; i++ )
			{
				/* Get the title of the column - unless there is a user set one */
				if ( _aoColumns[i].sTitle == null )
				{
					_aoColumns[i].sTitle = $('thead th:nth-child('+(i+1)+')', oTable).text();
				}
				
				/* Get the data for the column */
				$('tbody td:nth-child('+_aoColumns.length+'n+'+(i+1)+')', oTable).each( function( index ) {
					if ( typeof _aaData[index] != 'object' )
					{
						_aaData[index] = new Array();
					}
					_aaData[index][i] = this.innerHTML;
					
					/* Check if the user has set a type, or if we should auto detect */
					if ( _aoColumns[i].sType == null )
					{
						_aoColumns[i].sType = _fnDetectType( _aaData[index][i] );
					}
					else if ( _aoColumns[i].sType == "date" || 
					          _aoColumns[i].sType == "numeric" )
					{
						/* If type is date or numeric - ensure that all collected data
						 * in the column is of the same type
						 */
						_aoColumns[i].sType = _fnDetectType( _aaData[index][i] );
					}
					/* The else would be 'type = string' we don't want to do anything
					 * if that is the case
					 */
				} );
			}
		}
		
		
		/*
		 * Function: _fnDetectType
		 * Purpose:  Get the sort type based on an input string
		 * Returns:  string:
		 *   - 'string'
		 *   - 'numeric'
		 *   - 'date'
		 * Inputs:   string:sData - data we wish to know the type of
		 */
		function _fnDetectType( sData )
		{
			if ( _fnIsNumeric(sData) )
			{
				return 'numeric';
			}
			else if ( ! isNaN(Date.parse(sData) ) )
			{
				return 'date';
			}
			else
			{
				return 'string';
			}
		}
		
		
		/*
		 * Function: _fnIsNumeric
		 * Purpose:  Check to see if a string is numeric
		 * Returns:  bool:bIsNumber - true:is number, false:not number
		 * Inputs:   string:sText - string to check
		 */
		function _fnIsNumeric ( sText )
		{
			var ValidChars = "0123456789.";
			var Char;
			
			for ( i=0 ; i<sText.length ; i++ ) 
			{ 
				Char = sText.charAt(i); 
				if (ValidChars.indexOf(Char) == -1) 
				{
					return false;
				}
			}
			
			return true;
		}
		
		
		/*
		 * Function: _fnDrawHead
		 * Purpose:  Create the HTML header for the table
		 * Returns:  -
		 * Inputs:   object:oTable - jQuery object for the target
		 *           int:iSortCol - column being sorted on
		 */
		function _fnDrawHead( oTable, iSortCol )
		{
			var nTr = document.createElement( "tr" );
			var nTh;
			
			for ( var i=0 ; i<_aoColumns.length ; i++ )
			{
				if ( _aoColumns[i].bVisible )
				{
					nTh = document.createElement( "th" );
					
					if ( i == iSortCol )
					{
						nTh.className = "sorting_asc";
					}
					
					var sWidth = '';
					if ( _aoColumns[i].sWidth != null )
					{
						nTh.style.width = _aoColumns[i].sWidth;
					}
					
					nTh.innerHTML = _aoColumns[i].sTitle;
					nTr.appendChild( nTh );
				}
			}
			
			/* Add the new header */
			$('thead', oTable).html( '' )[0].appendChild( nTr );
			
			/* If there is a footer, copy the header into it */
			if ( oTable.getElementsByTagName('tfoot').length != 0 )
			{
				var nFoot = nTr.cloneNode( true );
				$("th", nFoot).removeClass("sorting_asc");
				$('tfoot', oTable).html( '' )[0].appendChild( nFoot );
			}
			
			/* Add sort listener */
			if ( _oFeatures.bSort )
			{
				$('thead th', oTable).click( function() {
					if ( _oFeatures.bProcessing )
					{
						_fnProcessingDisplay( true );
					}
					
					/* Convert the column index to data index */
					var iDataIndex = $("thead th", oTable).index(this); /* back up */
					
					for ( var i=0 ; i<_aoColumns.length ; i++ )
					{
						if ( this.innerHTML == _aoColumns[i].sTitle )
						{
							iDataIndex = i;
							break;
						}
					}
					
					/* Run the sort */
					_fnSort( oTable, iDataIndex );
					
					/* Remove previous sort */
					$("thead th", oTable).removeClass( "sorting_asc" ).removeClass( "sorting_desc" );
					
					/* Set the class name for the sorting th */
					if ( _iSortingDirection == 0 )
						this.className = "sorting_asc";
					else
						this.className = "sorting_desc";
					
					if ( _oFeatures.bProcessing )
					{
						_fnProcessingDisplay( false );
					}
				} );
			}
			
			/* Set an absolute width for the table such that pagination doesn't
			 * cause the table to resize
			 */
			oTable.style.width = oTable.offsetWidth+"px";
		}
		
		
		/*
		 * Function: _fnDraw
		 * Purpose:  Create the HTML needed for the table and write it to the page
		 * Returns:  -
		 * Inputs:   object:oTable - jQuery object for the target
		 */
		function _fnDraw( oTable )
		{
			var anRows = new Array();
			var sOutput = "";
			var iRowCount = 0;
			var nTd;
			var i;
			
			if ( _aaData.length != 0 )
			{
				for ( var j=_iDisplayStart ; j<_iDisplayEnd ; j++ )
				{
					anRows[ iRowCount ] = document.createElement( 'tr' );
					
					/* Class names for striping */
					if ( _asStripClasses.length > 0 )
					{
						anRows[ iRowCount ].className =
							_asStripClasses[ iRowCount % _asStripClasses.length ];
					}
					
					for ( i=0 ; i<_aoColumns.length ; i++ )
					{
						/* Ensure that we are allow to display this column */
						if ( _aoColumns[i].bVisible )
						{
							nTd = document.createElement( 'td' );
							nTd.setAttribute( 'valign', "top" );
							
							if ( _iColumnSorting == i && _aoColumns[i].sClass != null )
							{
								nTd.className = _aoColumns[i].sClass + ' sorting';
							}
							else if ( _iColumnSorting == i )
							{
								nTd.className = 'sorting';
							}
							else if ( _aoColumns[i].sClass != null )
							{
								nTd.className = _aoColumns[i].sClass;
							}
							
							/* Check for a custom render - otherwise output the data */
							if ( typeof _aoColumns[i].fnRender == 'function' )
							{
								nTd.innerHTML = _aoColumns[i].fnRender( {
									"iDataRow": j,
									"iDataColumn": i,
									"aData": _aaData } );
							}
							else
							{
								nTd.innerHTML = _aaData[j][i];
							}
							
							anRows[ iRowCount ].appendChild( nTd );
						}
					}
					
					/* Custom row callback function - might want to manipule the row */
					if ( typeof _fnRowCallback == "function" )
					{
						anRows[ iRowCount ] = _fnRowCallback( anRows[ iRowCount ], _aaData[j], iRowCount, j );
					}
					
					iRowCount++;
				}
			}
			else
			{
				anRows[ 0 ] = document.createElement( 'tr' );
				nTd = document.createElement( 'td' );
				nTd.setAttribute( 'valign', "top" );
				nTd.colSpan = _aoColumns.length;
				nTd.style.textAlign = "center";
				nTd.innerHTML = _oLanguage.sZeroRecords;
				
				anRows[ iRowCount ].appendChild( nTd );
			}
			
			
			/* Put the draw table into the dom */
			var nBody = $('tbody', oTable);
			nBody.html( '' );
			for ( i=0 ; i<anRows.length ; i++ )
			{
				nBody[0].appendChild( anRows[i] );
			}
			
			/* Update the pagination display buttons */
			if ( _oFeatures.bPaginate )
			{
				document.getElementById( _sTableId+'_previous' ).className = 
					( _iDisplayStart == 0 ) ? "paginate_disabled_previous" : "paginate_enabled_previous";
				
				document.getElementById( _sTableId+'_next' ).className = 
					( _iDisplayEnd == _aaData.length ) ? "paginate_disabled_next" : "paginate_enabled_next";
			}
			
			/* Show information about the table */
			if ( _oFeatures.bInfo )
			{
				/* Update the information */
				if ( _aaData.length == 0 && _aaData.length == _aaDataMaster.length )
				{
					_nInfo.innerHTML = _oLanguage.sInfoEmtpy +' '+ _oLanguage.sInfoPostFix;
				}
				else if ( _aaData.length == 0 )
				{
					_nInfo.innerHTML = _oLanguage.sInfoEmtpy +' '+ 
						_oLanguage.sInfoFiltered.replace('_MAX_', _aaDataMaster.length) +' '+ 
						_oLanguage.sInfoPostFix;
				}
				else if ( _aaData.length == _aaDataMaster.length )
				{
					_nInfo.innerHTML = 
						_oLanguage.sInfo.
							replace('_START_',_iDisplayStart+1).
							replace('_END_',_iDisplayEnd).
							replace('_TOTAL_',_aaData.length) +' '+ 
						_oLanguage.sInfoPostFix;
				}
				else
				{
					_nInfo.innerHTML = 
						_oLanguage.sInfo.
							replace('_START_',_iDisplayStart+1).
							replace('_END_',_iDisplayEnd).
							replace('_TOTAL_',_aaData.length) +' '+ 
						_oLanguage.sInfoFiltered.replace('_MAX_', _aaDataMaster.length) +' '+ 
						_oLanguage.sInfoPostFix;
				}
			}
		}
		
		
		/*
		 * Function: _fnAddOptionsHtml
		 * Purpose:  Add the options to the page HTML for the table
		 * Returns:  -
		 * Inputs:   object:oTable - jQuery object for the target
		 */
		function _fnAddOptionsHtml( oTable )
		{
			/*
			 * Filter details
			 */
			if ( _oFeatures.bFilter )
			{
				var nFilter = document.createElement( 'div' );
				nFilter.setAttribute( 'id', _sTableId+'_filter' );
				nFilter.innerHTML = 
					_oLanguage.sSearch+' <input type="text" name="'+_sTableId+'_filter">';
				oTable.parentNode.insertBefore( nFilter, oTable );
				
				$("input[name='"+_sTableId+'_filter'+"']").keyup( 
					function() { _fnFilter( oTable, this.value ); } );
			}
			
			/*
			 * Information about the table
			 */
			if ( _oFeatures.bInfo )
			{
				_nInfo = document.createElement( 'div' );
				_nInfo.setAttribute( 'id', _sTableId+'_info' );
				$(_nInfo).insertAfter( oTable );
			}
			
			/*
			 * Paginate details
			 */
			if ( _oFeatures.bPaginate )
			{
				var nPaginate = document.createElement( 'div' );
				var nPrevious = document.createElement( 'div' );
				var nNext = document.createElement( 'div' );
				
				nPaginate.setAttribute( 'id', _sTableId+'_paginate' );
				nPrevious.setAttribute( 'id', _sTableId+'_previous' );
				nNext.setAttribute( 'id', _sTableId+'_next' );
				nPrevious.className = "paginate_disabled_previous";
				nNext.className = "paginate_disabled_next";
				
				nPaginate.appendChild( nPrevious );
				nPaginate.appendChild( nNext );
				$(nPaginate).insertAfter( oTable );
				
				$(nPrevious).click( function() {
					_iDisplayStart -= _iDisplayLength;
					
					/* Correct for underrun */
					if ( _iDisplayStart < 0 )
					  _iDisplayStart = 0;
					
					_fnCalculateEnd();
					_fnDraw( oTable );
				} );
				
				$(nNext).click( function() {
					/* Make sure we are not over running the display array */
					if ( _iDisplayStart + _iDisplayLength < _aaData.length )
						_iDisplayStart += _iDisplayLength;
					
					_fnCalculateEnd();
					_fnDraw( oTable );
				} );
				
				/*
				 * Display length
				 */
				if ( _oFeatures.bLengthChange )
				{
					/* This can be overruled by not using the _MENU_ var/macro in the language variable */
					var sStdMenu = 
						'<select size="1" name="'+_sTableId+'_length">'+
							'<option value="10">10</option>'+
							'<option value="25">25</option>'+
							'<option value="50">50</option>'+
							'<option value="100">100</option>'+
						'</select>';
					
					var nLength = document.createElement( 'div' );
					nLength.setAttribute( 'id', _sTableId+'_length' );
					nLength.innerHTML = _oLanguage.sLengthMenu.replace( '_MENU_', sStdMenu );
					
					oTable.parentNode.insertBefore( nLength, oTable );
					$('select', nLength).change( function() {
						_iDisplayLength = parseInt($(this).val());
						
						_fnCalculateEnd();
						_fnDraw( oTable );
					} );
				}
				
				/*
				 * Create a wrapper div around the table
				 */
				var nWrapper = document.createElement( 'div' );
				nWrapper.setAttribute( 'id', _sTableId+'_wrapper' );
				oTable.parentNode.insertBefore( nWrapper, oTable );
				nWrapper.appendChild( oTable );
			}
			
			/*
			 * Processing
			 */
			if ( _oFeatures.bProcessing )
			{
				_nProcessing = document.createElement( 'div' );
				_nProcessing.setAttribute( 'id', _sTableId+'_processing' );
				_nProcessing.appendChild( document.createTextNode( _oLanguage.sProcessing ) );
				_nProcessing.className = "dataTables_processing";
				_nProcessing.style.visibility = "hidden";
				oTable.parentNode.insertBefore( _nProcessing, oTable );
			}
		}
		
		/*
		 * Function: _fnProcessingDisplay
		 * Purpose:  Display or hide the processing indicator
		 * Returns:  -
		 * Inputs:   bool:
		 *   true - show the processing indicator
		 *   false - don't show
		 */
		function _fnProcessingDisplay ( bShow )
		{
			if ( bShow )
				_nProcessing.style.visibility = "visible";
			else
				_nProcessing.style.visibility = "hidden";
		}
		
		
		
		/*
		 * Function: _fnConvertToWidth
		 * Purpose:  Convert a CSS unit width to pixels (e.g. 2em)
		 * Returns:  int:iWidth - width in pixels
		 * Inputs:   string:sWidth - width to be converted
		 *           node:nParent - parent to get the with for (required for
		 *             relative widths) - optional
		 */
		function _fnConvertToWidth ( sWidth, nParent )
		{
			if ( !sWidth || sWidth==null || sWidth=='' )
			{
				return 0;
			}
			
			if ( typeof nParent == "undefined" )
			{
				nParent = document.getElementsByTagName('body')[0];
			}
			
			var iWidth;
			var nTmp = document.createElement( "div" );
			nTmp.style.width = sWidth;
			
			nParent.appendChild( nTmp );
			iWidth = nTmp.offsetWidth;
			nParent.removeChild( nTmp );
			
			return ( iWidth );
		}
		
		
		/*
		 * Function: _fnFilter
		 * Purpose:  Filter the data table based on user input and draw the table
		 * Returns:  -
		 * Inputs:   -
		 */
		function _fnFilter( oTable, sInput, iForce )
		{
			var flag, i, j;
			var aaDataSearch = new Array();
			
			if ( typeof iForce == 'undefined' || iForce == null )
				iForce = 0;
			
			/* Generate the regular expression to use. Something along the lines of:
			 * ^(?=.*?\bone\b)(?=.*?\btwo\b)(?=.*?\bthree\b).*$
			 */
			var asSearch = sInput.split( ' ' );
			var sRegExpString = '^(?=.*?'+asSearch.join( ')(?=.*?' )+').*$';
			var rpSearch = new RegExp( sRegExpString, "i" );
			
			/*
			 * If the input is blank - we want the full data set
			 */
			if ( sInput.length <= 0 )
			{
				_aaData.splice( 0, _aaData.length);
				_aaData = _aaDataMaster.slice();
			}
			else
			{
				/*
				 * We are starting a new search or the new search string is smaller 
				 * then the old one (i.e. delete). Search from the master array
			 	 */
				if ( _aaData.length == _aaDataMaster.length ||
					   _sPreviousSearch.length > sInput.length || iForce == 1 )
				{
					/* Wipe the old search array */
					aaDataSearch.splice( 0, aaDataSearch.length );
					_fnBuildSearchArray( 1 );
					
					/* Search through all records to populate the search array
					 * The the _aaDataMaster and asDataSearch arrays have 1 to 1 
					 * mapping
					 */
					for ( i=0 ; i<_aaDataMaster.length ; i++ )
					{
						if ( rpSearch.test(_asDataSearch[i]) )
						{
							aaDataSearch[aaDataSearch.length++] = _aaDataMaster[i];
						}
					}
					
					_aaData = aaDataSearch;
			  }
			  else
				{
			  	/* Using old search array - refine it - do it this way for speed
			  	 * Don't have to search the whole master array again
			 		 */
			  	var iIndexCorrector = 0;
			  	
			  	/* Search the current results - warning - indexes must match! */
			  	for ( i=0 ; i<_asDataSearch.length ; i++ )
					{
			  		if ( ! rpSearch.test(_asDataSearch[i]) )
						{
			  			_aaData.splice( i-iIndexCorrector, 1 );
			  			iIndexCorrector++;
			  		}
			  	}
			  }
				
				_sPreviousSearch = sInput;
			}
			
			/* Redraw the table */
			_iDisplayStart = 0; /* Start the user from the first page on filter */
			_fnCalculateEnd();
			_fnDraw( oTable );
			
			/* Rebuild search array 'offline' */
			_fnBuildSearchArray( 0 );
		}
		
		
		/*
		 * Function: _fnCalculateEnd
		 * Purpose:  Rcalculate the end point based on the start point
		 * Returns:  -
		 * Inputs:   -
		 */
		function _fnCalculateEnd()
		{
			/* Set the end point of the display - based on how many elements there are
			 * still to display
			 */
			if ( _iDisplayStart + _iDisplayLength > _aaData.length )
				_iDisplayEnd = _aaData.length;
			else
				_iDisplayEnd = _iDisplayStart + _iDisplayLength;
		}
		
		
		/*
	 	 * Function: _fnSort
		 * Purpose:  Change the order of the table
		 * Returns:  -
		 * Inputs:   node:nTable - the table we are interested in
		 *           int:iColumn - column number to be ordered
		 *           bool:bForce - force a resort - optional - default false
		 * Notes:    We always sort the master array and then apply a filter again
		 *   if it is needed. This probably isn't optimal - but atm I can't think
		 *   of any other way which is (each has disadvantages)
		 */
		_fnSort = function ( nTable, iColumn, bForce )
		{
			/* Check if column is sortable */
			if ( ! _aoColumns[ iColumn ].bSortable )
			{
				return;
			}
			
			if ( typeof bForce == 'undefined' )
				bForce = false;
			
			/* Find out if we are reversing the order of the array */
			if ( iColumn == _iColumnSorting && !bForce )
			{
				_aaDataMaster.reverse(); /* needs to be data master - and force */
				
				_iSortingDirection = (_iSortingDirection == 0) ? 1 : 0;
			}
			else
			{
				/* Need to sort the array */
				_iColumnSorting = iColumn;
				_iSortingDirection = 0;
				
				if ( typeof _aoColumns[ iColumn ].fnSort == 'function' )
				{
					/* Custom sort function */
					_aaDataMaster.sort( _aoColumns[ iColumn ].fnSort );
				}
				else if ( _aoColumns[ iColumn ].sType == 'numeric' )
				{
					/* Use numerical sorting */
					_aaDataMaster.sort( function ( a, b ) {
		    		return a[iColumn] - b[iColumn];
					} );
				}
				else if ( _aoColumns[ iColumn ].sType == 'date' )
				{
					/* Use date sorting */
					_aaDataMaster.sort( function ( a, b ) {
						var x = Date.parse( a[iColumn] );
						var y = Date.parse( b[iColumn] );
						return x - y;
					} );
				}
				else
				{
					/* Use default alphabetical sorting */
					_aaDataMaster.sort( function ( a, b ) {
						var x = a[iColumn];
						var y = b[iColumn];
						return ((x < y) ? -1 : ((x > y) ? 1 : 0));
					} );
				}
			}
			
			/* Copy the master data into the draw array and re-draw */
			if ( _oFeatures.bFilter )
				_fnFilter( nTable, _sPreviousSearch, 1 );
			else
				_aaData = _aaDataMaster.slice();
			
			_fnDraw( nTable );
		}
		
		
		/*
		 * Function: _fnBuildSearchArray
		 * Purpose:  Create an array which can be quickly search through
		 * Returns:  -
		 * Inputs:   int:iMaster - use the master data array - optional
		 */
		function _fnBuildSearchArray ( iMaster )
		{
			/* Clear out the old data */
			_asDataSearch.splice( 0, _asDataSearch.length );
			
			var aArray = (typeof iMaster != 'undefined' && iMaster == 1) ?
			 	_aaDataMaster : _aaData;
			
			for ( i=0 ; i<aArray.length ; i++ )
			{
				_asDataSearch[i] = '';
				for ( j=0 ; j<_aoColumns.length ; j++ )
				{
					if ( _aoColumns[j].bSearchable )
					{
						_asDataSearch[i] += aArray[i][j]+' ';
					}
				}
			}
		}
		
		
		/*
		 * Function: _fnCalculateColumnWidths
		 * Purpose:  Calculate the width of columns for the table
		 * Returns:  -
		 * Inputs:   node:nTable - table in question
		 */
		function _fnCalculateColumnWidths ( nTable )
		{
			var iTableWidth = nTable.offsetWidth;
			var iTotalUserIpSize = 0;
			var iTmpWidth;
			var iVisibleColumns = 0;
			var i;
			var oHeaders = $('thead th', nTable);
			
			/* Convert any user input sizes into pixel sizes */
			for ( var i=0 ; i<_aoColumns.length ; i++ )
			{
				if ( _aoColumns[i].bVisible )
				{
					iVisibleColumns++;
					
					if ( _aoColumns[i].sWidth != null )
					{
						iTmpWidth = _fnConvertToWidth( _aoColumns[i].sWidth, 
							nTable.parentNode );
						
						/* Total up the user defined widths for later calculations */
						iTotalUserIpSize += iTmpWidth;
						
						_aoColumns[i].sWidth = iTmpWidth+"px";
					}
				}
			}
			
			/* If the number of columns in the DOM equals the number that we
			 * have to process in dataTables, then we can use the offsets that are
			 * created by the web-browser. No custom sizes can be set in order for
			 * this to happen
			 */
			if ( _aoColumns.length == oHeaders.length && iTotalUserIpSize == 0 )
			{
				for ( i=0 ; i<_aoColumns.length ; i++ )
				{
					_aoColumns[i].sWidth = oHeaders[i].offsetWidth+"px";
				}
			}
			else
			{
				/* Otherwise we are going to have to do some calculations to get
				 * the width of each column. Construct a 1 row table with the maximum
				 * string sizes in the data, and any user defined widths
				 */
				var nCalcTmp = nTable.cloneNode( false );
				nCalcTmp.setAttribute( "id", '' );
				
				var sTableTmp = '<table class="'+nCalcTmp.className+'">';
				var sCalcHead = "<tr>";
				var sCalcHtml = "<tr>";
				
				/* Construct a tempory table which we will inject (invisibly) into
				 * the dom - to let the browser do all the hard word
				 */
				for ( var i=0 ; i<_aoColumns.length ; i++ )
				{
					if ( _aoColumns[i].bVisible )
					{
						sCalcHead += '<th>'+_aoColumns[i].sTitle+'</th>';
						
						if ( _aoColumns[i].sWidth != null )
						{
							var sWidth = '';
							if ( _aoColumns[i].sWidth != null )
							{
								sWidth = ' style="width:'+_aoColumns[i].sWidth+';"';
							}
							
							sCalcHtml += '<td'+sWidth+' tag_index="'+i+'">'+fnGetMaxLenString(i)+'</td>';
						}
						else
						{
							sCalcHtml += '<td tag_index="'+i+'">'+fnGetMaxLenString(i)+'</td>';
						}
					}
				}
				
				sCalcHead += "</tr>";
				sCalcHtml += "</tr>";
				
				/* Create the tmp table node (thank you jQuery) */
				nCalcTmp = $( sTableTmp + sCalcHead + sCalcHtml +'</table>' )[0];
				nCalcTmp.style.width = iTableWidth + "px";
				nCalcTmp.style.visibility = "hidden";
				nCalcTmp.style.position = "absolute"; /* Try to aviod scroll bar */
				
				nTable.parentNode.appendChild( nCalcTmp );
				
				var oNodes = $("td", nCalcTmp);
				var iIndex;
				
				/* Gather in the browser calculated widths for the rows */
				for ( i=0 ; i<oNodes.length ; i++ )
				{
					iIndex = oNodes[i].getAttribute('tag_index');
					
					_aoColumns[iIndex].sWidth = $("td", nCalcTmp)[i].offsetWidth +"px";
				}
				
				nTable.parentNode.removeChild( nCalcTmp );
			}
		}
		
		
		/*
		 * Function: fnGetMaxLenString
		 * Purpose:  Get the maximum strlen for each data column
		 * Returns:  string: - max strlens for each column
		 * Inputs:   int:iCol - column of interest
		 */
		function fnGetMaxLenString( iCol )
		{
			var iMax = 0;
			var iMaxIndex = -1;
			
			for ( var i=0 ; i<_aaDataMaster.length ; i++ )
			{
				if ( _aaDataMaster[i][iCol].length > iMax )
				{
					iMax = _aaDataMaster[i][iCol].length;
					iMaxIndex = i;
				}
			}
			
			if ( iMaxIndex >= 0 )
				return _aaDataMaster[iMaxIndex][iCol];
			else
				return '';
		}
		
		
		/*
		 * Function: _fnArrayCmp
		 * Purpose:  Compare two arrays
		 * Returns:  0 if match, 1 if length is different, 2 if no match
		 * Inputs:   array:aArray1 - first array
		 *           array:aArray2 - second array
		 */
		function _fnArrayCmp( aArray1, aArray2 )
		{
			if ( aArray1.length != aArray2.length )
			{
				return 1;
			}
			
			for ( var i=0 ; i<aArray1.length ; i++ )
			{
				if ( aArray1[i] != aArray2[i] )
				{
					return 2;
				}
			}
			
			return 0;
		}
		
		
		/*
		 * Function: _fnMasterIndexFromDisplay
		 * Purpose:  Get the master index from the display index
		 * Returns:  int:i - index
		 * Inputs:   int:iIndexAAData - display array index
		 */
		function _fnMasterIndexFromDisplay( iIndexAAData )
		{
			var i = 0;
			
			while ( _fnArrayCmp( _aaDataMaster[i], _aaData[iIndexAAData] ) != 0 )
			{
				i++;
			}
			
			return i;
		}
		
		
		/*
		 * Function: _fnLanguageProcess
		 * Purpose:  Copy language variables from remote object to a local one
		 * Returns:  -
		 * Inputs:   object:oLanguage - Language information
		 */
		function _fnLanguageProcess( oLanguage )
		{
			if ( typeof oLanguage.sProcessing != 'undefined' )
				_oLanguage.sProcessing = oLanguage.sProcessing;
			
			if ( typeof oLanguage.sLengthMenu != 'undefined' )
				_oLanguage.sLengthMenu = oLanguage.sLengthMenu;
			
			if ( typeof oLanguage.sZeroRecords != 'undefined' )
				_oLanguage.sZeroRecords = oLanguage.sZeroRecords;
			
			if ( typeof oLanguage.sInfo != 'undefined' )
				_oLanguage.sInfo = oLanguage.sInfo;
			
			if ( typeof oLanguage.sInfoEmtpy != 'undefined' )
				_oLanguage.sInfoEmtpy = oLanguage.sInfoEmtpy;
			
			if ( typeof oLanguage.sInfoFiltered != 'undefined' )
				_oLanguage.sInfoFiltered = oLanguage.sInfoFiltered;
			
			if ( typeof oLanguage.sInfoPostFix != 'undefined' )
				_oLanguage.sInfoPostFix = oLanguage.sInfoPostFix;
			
			if ( typeof oLanguage.sSearch != 'undefined' )
				_oLanguage.sSearch = oLanguage.sSearch;
			
			_fnInitalise();
		}
		
		
		/*
		 * Function: _fnInitalise
		 * Purpose:  Draw the table for the first time, adding all required features
		 * Returns:  -
		 * Inputs:   -
		 */
		function _fnInitalise ( )
		{
			/* Ensure that the table data is fully initialised */
			if ( _bInitialised == false )
			{
				setTimeout( function(){ _fnInitalise() }, 2000 );
			}
			
			/* Show the display HTML options */
			_fnAddOptionsHtml( _nTable );
			
			/* Draw the headers for the table */
			_fnDrawHead( _nTable, _iDefaultSortIndex );
			
			/* If there is default sorting required - let's do it. The sort function
			 * will do the drawing for us. Otherwise we draw the table
			 */
			if ( _oFeatures.bSort )
			{
				_fnSort( _nTable, _iDefaultSortIndex );
			}
			else
			{
				_fnDraw( _nTable );
			}
		}
		
		
		
		/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Construct
		 */
		return this.each(function()
		{
			var bInitHandedOff = false;
			var bUsePassedData = false;
			
			/* Store the features that we have available */
			if ( typeof oInit != 'undefined' && oInit != null )
			{
				if ( typeof oInit.bPaginate != 'undefined' )
					_oFeatures.bPaginate = oInit.bPaginate;
				
				if ( typeof oInit.bLengthChange != 'undefined' )
					_oFeatures.bLengthChange = oInit.bLengthChange;
				
				if ( typeof oInit.bFilter != 'undefined' )
					_oFeatures.bFilter = oInit.bFilter;
				
				if ( typeof oInit.bSort != 'undefined' )
					_oFeatures.bSort = oInit.bSort;
				
				if ( typeof oInit.bInfo != 'undefined' )
					_oFeatures.bInfo = oInit.bInfo;
				
				if ( typeof oInit.bProcessing != 'undefined' )
					_oFeatures.bProcessing = oInit.bProcessing;
				
				if ( typeof oInit.bAutoWidth != 'undefined' )
					_oFeatures.bAutoWidth = oInit.bAutoWidth;
				
				if ( typeof oInit.aaData != 'undefined' )
					bUsePassedData = true;
				
				if ( typeof oInit.iDisplayLength != 'undefined' )
					_iDisplayLength = oInit.iDisplayLength;
				
				if ( typeof oInit.asStripClasses != 'undefined' )
					_asStripClasses = oInit.asStripClasses;
				else
					_asStripClasses = [ "odd", "even" ];
				
				if ( typeof oInit.fnRowCallback != 'undefined' )
					_fnRowCallback = oInit.fnRowCallback;
				
				if ( typeof oInit.iDefaultSortIndex != 'undefined' )
					_iDefaultSortIndex = oInit.iDefaultSortIndex;
				
				/* Backwards compatability */
				/* aoColumns / aoData - remove in 1.2 */
				if ( typeof oInit != 'undefined' && typeof oInit.aoData != 'undefined' )
				{
					oInit.aoColumns = oInit.aoData;
				}
				
				/* Language definitions */
				if ( typeof oInit.oLanguage != 'undefined' )
				{
					bInitHandedOff = true;
					
					if ( oInit.oLanguage.sUrl != 'undefined' )
					{
						/* Get the language definitions from a file */
						_oLanguage.sUrl = oInit.oLanguage.sUrl;
						$.getJSON( _oLanguage.sUrl, null, _fnLanguageProcess );
					}
					else
					{
						_fnLanguageProcess( oInit.oLanguage );
					}
				}
				/* Warning: The _fnLanguageProcess function is async to the remainder of this function due
				 * to the XHR. We use _bInitialised in _fnLanguageProcess() to check this the processing 
				 * below is complete. The reason for spliting it like this is optimisation - we can fire
				 * off the XHR (if needed) and then continue processing the data.
				 */
			}
			
			/* Set the id */
			_sTableId = this.getAttribute( 'id' );
			
			/* Set the table node */
			_nTable = this;
			
			/* See if we should load columns automatically or use defined ones */
			if ( typeof oInit != 'undefined' && typeof oInit.aoColumns != 'undefined' )
			{
				for ( var i=0 ; i<oInit.aoColumns.length ; i++ )
				{
					_fnAddColumn( oInit.aoColumns[i] );
				}
			}
			else
			{
				$('thead th', this).each( function() { _fnAddColumn( null ) } );
			}
			
			/* Check if there is data passing into the constructor */
			if ( bUsePassedData )
			{
				_aaDataMaster = oInit.aaData.slice();
				/* Add a thead and tbody to the table */
			 	$(this).html( '<thead></thead><tbody></tbody>' );
			}
			else
			{
				/* Grab the data from the page */
				_fnGatherData( this );
				
				/* Copy the data array */
				_aaDataMaster = _aaData.slice();
			}
			
			/* Calculate sizes for columns */
			if ( _oFeatures.bAutoWidth )
			{
				_fnCalculateColumnWidths( this );
			}
			
			/* Initialisation complete - table can be drawn */
			_bInitialised = true;
			
			/* Check if we need to initialise the table (it might not have been handed off to the
			 * language processor)
			 */
			if ( bInitHandedOff == false )
			{
				_fnInitalise()
			}
		})
	}
})(jQuery);
