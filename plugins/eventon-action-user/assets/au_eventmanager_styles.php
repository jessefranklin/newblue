<?php
// styles for event manager thats printed on to the page
?>

.evoau_manager_event_section{
	width:100%;
	overflow:hidden;
}
.eventon_actionuser_eventslist,
.evoau_manager_event{float:left; width:100%}
.evoau_delete_trigger{
	position: absolute;
    height: 100%;
    width: 100%;
    background-color: rgba(255, 255, 255, 0.79);
    text-align: center;
    vertical-align: middle;
    display: block;
    z-index: 5;
    display: flex;
    justify-content: center;
}
.evoau_delete_trigger .deletion_message{
    position:absolute; height:100px; display:block
}
.evoau_delete_trigger span{
	display: inline-block;
    border-radius: 20px;
    margin: 15px 5px;
    padding: 5px 20px;
    background-color: #59c9ff;
    color: #fff;
    cursor:pointer;
}
.evoau_delete_trigger span:hover{background-color:#32bbfd}
.evoau_manager_event_rows{border:1px solid #E2E2E2;border-radius:5px;}
.evcal_btn.evoau, .evoau_submission_form.loginneeded .evcal_btn{
	border-radius: 4px;
	border: none;
	color: #ffffff;
	background: #237ebd;
	text-transform: uppercase;
	text-decoration: none;
	border-radius: 4px;
	border-bottom: none;
	font: bold 14px arial;
	display: inline-block;
	padding: 8px 12px;
	margin-top: 4px;
	box-shadow:none; transition:none
}
.evcal_btn.evoau:hover, .evoau_submission_form.loginneeded .evcal_btn:hover{color: #fff; opacity: 0.6;box-shadow:none}
.eventon_actionuser_eventslist{
	overflow:hidden;
	position:relative
}
a.evoau_back_btn, .evoau_paginations{
	text-decoration:none;
	box-shadow:none;
	position:relative;
	padding:10px 0;
	cursor:pointer;
}
a.evoau_back_btn:hover, .evoau_paginations:hover{
	box-shadow:none;
	text-decoration:none
}
.evoau_back_btn i, .evoau_paginations i{
	border-radius: 50%;
    height: 30px;
    width: 30px;
    display: inline-block;
    border: 1px solid #808080;
    text-align: center;
    font-size: 16px;
    position: relative;
    padding-top: 5px;
    margin:10px 10px 0px 10px;
}
.evoau_back_btn:hover i, .evoau_paginations:hover i{background-color:#808080}
.evoau_back_btn:hover i:before, .evoau_paginations:hover i:before{color:#fff}
.evoau_back_btn i:before{
	margin-top:5px;
}
.eventon_actionuser_eventslist a, .eventon_actionuser_eventslist a:hover{
	box-shadow:none; -webkit-box-shadow:none;
}
.eventon_actionuser_eventslist .evoau_manager_row{
	padding:10px 10px; margin: 0;
	border-bottom:1px solid #E2E2E2; position:relative;
}
.eventon_actionuser_eventslist .evoau_manager_row:last-child{border:none}
.eventon_actionuser_eventslist .evoau_manager_row p{padding:0; margin:0}
.eventon_actionuser_eventslist .evoau_manager_row:hover{
	background-color: #FCF7F3;
}
#evoau_event_manager h2.title{
	padding: 10px 0;
    border-bottom: 1px solid #afafaf;
    border-top: 1px solid #afafaf;
}
.eventon_actionuser_eventslist .evoau_manager_row span{
	opacity: 0.7;
	font-style: italic;
	display: block;
	font-size: 11px;
	text-transform: uppercase;
}
.eventon_actionuser_eventslist .evoau_manager_row p tags{
	display:inline-block;
	font-size:11px;
	border-radius:5px;
	margin-left:5px;
	padding:2px 5px;
	background-color:#3d3d3d;
	color:#fff;
	text-transform:uppercase;
}
.eventon_actionuser_eventslist .evoau_manager_row a.evoauem_additional_buttons{
	cursor:pointer;
	display:inline-block;
	border-radius:5px;
	margin-right:5px;
	padding:2px 7px;
	background-color:#969696;
	color:#fff;
	font-size:11px;
	text-transform:uppercase;
}
.eventon_actionuser_eventslist .evoau_manager_row a.evoauem_additional_buttons:hover{
	text-decoration:none; opacity:0.6
}
.eventon_actionuser_eventslist p subtitle{
	font-weight:bold; text-transform:uppercase;
	font-family:roboto,oswald,'arial narrow';
	color:#808080;
	font-size:18px;
}
.eventon_actionuser_eventslist p subtitle a{color:#808080}
.eventon_actionuser_eventslist .evoau_manager_row span em{
	padding:1px 5px 2px; background-color:#EAEAEA; display:inline-block; border-radius:5px; margin-bottom:5px;
}
.eventon_actionuser_eventslist span.evoauem_event_tag{display:block}
.eventon_actionuser_eventslist span.evoauem_event_tag i{
    display:inline-block;
    font-style:normal;
    font-size:10px;
    padding:3px 12px 2px;
    border-radius:25px;
    background-color:#9a9a9a;
    color:#fff;
    margin-bottom:3px;
}
.eventon_actionuser_eventslist span.evoauem_event_tag.evo_event_tag_primary i{background-color:#ff5a5a}
.eventon_actionuser_eventslist a.editEvent, .eventon_actionuser_eventslist a.deleteEvent{
	opacity: 0.8;
    z-index: 1;
    text-align: center;
    position: absolute;
    right: 20px;
    top: 50%;
    height: 40px;
    width: 40px;
    padding-top: 8px;
    color: #333;
    cursor: pointer;
    border-radius: 50%;
    border: 1px solid #3d3d3d;
    font-size: 20px;
    margin-top: -20px;
    opacity: 0.5;
}
.eventon_actionuser_eventslist a.deleteEvent{
	right:70px;
}
.eventon_actionuser_eventslist .editEvent:hover, .eventon_actionuser_eventslist .deleteEvent:hover{
	text-decoration: none; opacity: 1;background-color:#3d3d3d; color:#fff; }

.eventon_actionuser_eventslist .editEvent:before, .eventon_actionuser_eventslist .deleteEvent:before{
	font-family: evo_FontAwesome;
}
.eventon_actionuser_eventslist em{clear: both;}
h3.evoauem_del_msg{padding: 4px 12px; border-radius: 5px; text-transform: uppercase;}
p.evoau_outter_shell{
	padding:20px; border-radius:5px; border:1px solid #d6d6d6;
}
.evoau_sub_formfield{border-left:4px solid #c1c1c1;}
/*#eventon_form .evoau_table .evoau_sub_formfield .row{border:none; padding:0}*/
.evoau_table p.minor_notice{ font-size:14px; background-color:#f5dfdf; padding:5px 10px}

.evoau_table span.ajdeToolTip{
    padding-left: 0;
    margin-left: 4px;
    text-align: center;
    font-style: normal;
    position: absolute;
    width: 13px;
    height: 14px;
    line-height: 110%;
    opacity: 0.4;
    border-radius: 0px;
    color: #fff;
    padding: 0;
    font-style: normal;
    cursor: pointer;
    display: inline-block;
    margin-top:3px;
}
.evoau_table .ajdeToolTip:before {
    content: "\f06a";
    font-style: normal;
    display: inline-block;
    color: #9d9d9d;
    font-size: 18px;
    margin-top: 2px;
}
.evoau_table .ajdeToolTip em{
    visibility: hidden;
    font: 12px 'open sans';
    position: absolute;
    left: -1px;
    width: 200px;
    background-color: #6B6B6B;
    border-radius: 0px;
    color: #fff;
    padding: 6px 8px;
    bottom: 22px;
    z-index: 900;
    text-align: center;
    margin-left: 8px;
    opacity: 0;
    -webkit-transition: opacity 0.2s, -webkit-transform 0.2s;
    transition: opacity 0.2s, transform 0.2s;
    -webkit-transform: translateY(-15px);
    transform: translateY(-15px);
    pointer-event: none;
}
.evoau_table .ajdeToolTip em:before{
    content: "";
    width: 0px;
    height: 0px;
    border-style: solid;
    border-width: 9px 9px 0 0;
    border-color: #6B6B6B transparent transparent transparent;
    position: absolute;
    bottom: -9px;
    left: 0px;
}
.evoau_table .ajdeToolTip:hover { opacity: 1;}
.evoau_table .ajdeToolTip:hover em {
    opacity: 1;
    visibility: visible;
    -webkit-transform: translateY(0);
    transform: translateY(0);
}
.edit_special{background-color:#f7f7f7;}
#eventon_form .evoau_table .edit_special .row{border-color:#e8e8e8}
.evoauem_section_subtitle{
    padding:10px 0; margin:0px;
    border-bottom:1px solid #afafaf;
    font-size:18px;
}
/* Pagination */
.evoau_manager_pagination{  padding-top:10px;    display: flex;   justify-content: space-between;}
.evoau_manager_pagination .evoau_paginations.next i{margin-right:10px; margin-left:10px}


@media (max-width: 480px){
	.eventon_actionuser_eventslist .editEvent, .eventon_actionuser_eventslist .deleteEvent{
		width:30px;
	}
	.eventon_actionuser_eventslist .deleteEvent{right:30px;}
}

<?php do_action('evoauem_styles');?>
