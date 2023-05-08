<?php

defined( 'ABSPATH' ) || exit;

class RC_PDF_Exports extends RC_Registrations_List {

	function get_meta($registration,$meta_name) {
		return stripslashes($registration[$meta_name]);
	}

	function get_parent_cell($order_id) {
		return $this->format_phone(get_post_meta($order_id, 'billing_phone_cell', true));
	}

	function get_parent_work_phone($order_id) {
		$order = wc_get_order( $order_id );
		return $this->format_phone($order->get_billing_phone());
	}

	function get_parent_first_name($order_id) {
		$order = wc_get_order( $order_id );
		return $order->get_billing_first_name();
	}

	function get_parent_last_name($order_id) {
		$order = wc_get_order( $order_id );
		return $order->get_billing_last_name();
	}

	function get_parent_email($order_id) {
		$order = wc_get_order( $order_id );
		return $order->get_billing_email();
	}

	function get_emergency_contact($order_id) {
        return  get_post_meta( $order_id, 'emergency_contact', true );
	}

	function get_emergency_contact_relationship($order_id) {
        return  get_post_meta( $order_id, 'emergency_contact_relationship', true );
	}

	function get_emergency_phone($order_id) {
        return $this->format_phone(get_post_meta($order_id, 'emergency_contact_phone', true ));
	}

	function get_church_home($order_id) {
		return get_post_meta($order_id, 'church_home', true);
	}

	function format_phone($original_phone) {
	    if(!$original_phone) {return '';}
	    $phone = preg_replace("/[^0-9]/", "", $original_phone );
	    if(strlen($phone)==11 && substr($phone,0,1)=='1') {
	        $phone = substr($phone,1);
	    }
	    if(strlen($phone)==10) {
	        return substr($phone,0,3) . '-' . substr($phone,3,3) . '-' . substr($phone,6)  ;
	    }
	    return $original_phone;
	}

	function get_age_group_report_html($camp) {
		$RC_Registrations_List = new RC_Registrations_List;
		$registrations = $RC_Registrations_List->get_rc_registrations( ['camp' => $camp, 'order_by' => 'carpool']);


		$age_old = '';
		$gender_old = '';
		$page_content = array();
		$page_no = 0;
		foreach ($registrations as $registration) {

			$age = $registration["age"];
			$gender = strtolower($registration["gender"]);
            $name = $this->get_meta($registration,"rc_firstname") . ' ' . $this->get_meta($registration,"rc_lastname");

			ob_start();

			if ($age != $age_old || $gender != $gender_old) {
				// start a new page
				$page_no++;
				?>
                <div class="section"><?php echo $age; ?> year old <?php echo $gender; ?></div>
                <div class="spacing2">&nbsp;</div>
				<?php
			}
			?>
            <style>
                .section {
                    border-bottom: 3px solid black;
                    font-size: 24pt;
                    text-align: center;
                    line-height: 1;
                    text-transform: uppercase;
                    font-weight: bold;
                }

                .spacing2 {
                    line-height: .3;
                }

                table {
                    width: 7.5in;
                }

                .spacing {
                    font-size: 3pt;
                }

                .box {
                    font-size: 12pt;
                    border: 1px solid #ddd;
                    line-height: 2.5;
                }

                .underline {
                    line-height: .8;
                    border-bottom: 2px solid black;
                }
            </style>

            <table cellpadding="0" cellspacing="0" nobr="true">
                <tr>
                    <td>
                        <table cellpadding="0" cellspacing="1pt">
                            <tr>
                                <td><?php echo $registration["group"]; ?></td>
                                <td><strong><?php echo $name; ?></strong></td>
                                <td>Precautions</td>
                                <td>Church</td>
                            </tr>
                            <tr>
                                <td><?php echo $age; ?> year old <?php echo $gender; ?></td>
                                <td><?php echo $registration["rc_shirt"]; ?></td>
                                <td><strong><?php echo $this->get_meta($registration,"rc_medical"); ?></strong></td>
                                <td><?php echo $this->get_church_home($registration["order_id"]); ?></td>
                            </tr>
                            <tr>
                                <td colspan="4">Parent cell: <strong><?php echo $this->get_parent_cell($registration["order_id"]); ?></strong></td>
                            </tr>
                        </table>
                        <div class="spacing">
                            <br>
                            <div class="box">&nbsp;</div>
                        </div>
                        <strong>Emergency info:</strong>
                        <div class="underline"></div>
                    </td>
                </tr>
            </table>
			<?php
			$page_content[$page_no] .= ob_get_clean();
			$age_old = $age;
			$gender_old = $gender;
		}

		return $page_content;
	}

	function generate_age_group_report($camp) {

		// include the main TCPDF library
		require_once(RC_PATH . '/includes/admin-pages/includes/tcpdf/tcpdf.php');

		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'in', 'LETTER', true, 'UTF-8', false);
		$pdf->SetMargins(.5, .5);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor("Runner's Camp");
		$pdf->SetTitle("Age Group Report" . ($camp ? ': ' . $camp : ''));

		// remove default header/footer
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// allow page breaking
		$pdf->SetAutoPageBreak(true, .5);

		// disable font subsetting
		$pdf->setFontSubsetting(false);

		// set font
		$pdf->SetFont('helvetica');

		// ---------------------------------------------------------


		// insert content
		$html = $this->get_age_group_report_html($camp);
		foreach ($html as $page) {
			$pdf->AddPage();
			$page = preg_replace('~>\s*\n\s*<~', '><', $page); // remove whitespace
			$pdf->writeHTML($page);
		}


		while (ob_get_level()) {
			ob_end_clean();
		}

		// output pdf
		$filename = $camp
            ? "age_group_report_" . strtolower(str_replace(" ","_",$camp))
            : "age_group_report";
		$pdf->Output($filename . ".pdf");
	}

	function get_roster_html($camp) {
		$RC_Registrations_List = new RC_Registrations_List;
		$registrations = $RC_Registrations_List->get_rc_registrations( ['camp' => $camp, 'order_by' => 'carpool']);


		$age_old = '';
		$gender_old = '';
		$page_content = array();
		$page_no = 0;
		foreach ($registrations as $registration) {

			$age = $registration["age"];
			$gender = strtolower($registration["gender"]);

			ob_start();

			if ($age != $age_old || $gender != $gender_old) {
				// start a new page
				if ($page_no>0) {
			        $page_content[$page_no] .= '</table>';
			    }
				$page_no++;
				?>
                <style>
                    .section {
                        border-bottom: 3px solid black;
                        font-size: 24pt;
                        text-align: center;
                        line-height: 1;
                        text-transform: uppercase;
                        font-weight: bold;
                    }

                    .spacing {
                        line-height: .5;
                    }

                    table {
                    border-collapse: collapse;
                    }

                    td {
                        border: 1px solid #ddd;
                        font-size: 9pt;
                    }
                </style>
                <div class="section"><?php echo $age; ?> year old <?php echo $gender; ?></div>
                <div class="spacing">&nbsp;</div>

                <table cellpadding="3" cellspacing="0">
                <tr style="font-weight: bold">
                    <td>Camper<br>First Name</td>
                    <td>Camper<br>Last Name</td>
                    <td>Special Information</td>
                    <td>Parent/Guardian<br>First Name</td>
                    <td>Parent/Guardian<br>Last Name</td>
                    <td>Parent/Guardian<br>Cell Phone</td>
                </tr>
				<?php
			}
			?>
            <tr>
                <td><?php echo $this->get_meta($registration,"rc_firstname"); ?></td>
                <td><?php echo $this->get_meta($registration,"rc_lastname"); ?></td>
                <td><?php echo $this->get_meta($registration,"rc_medical"); ?></td>
                <td><?php echo $this->get_parent_first_name($registration["order_id"]); ?></td>
                <td><?php echo $this->get_parent_last_name($registration["order_id"]); ?></td>
                <td><?php echo $this->get_parent_cell($registration["order_id"]); ?></td>
            </tr>
			<?php
			$page_content[$page_no] .= ob_get_clean();
			$age_old = $age;
			$gender_old = $gender;
		}

		$page_content[$page_no] .= '</table>';
		return $page_content;
	}

	function generate_roster($camp) {

		// include the main TCPDF library
		require_once(RC_PATH . '/includes/admin-pages/includes/tcpdf/tcpdf.php');

		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'in', 'LETTER', true, 'UTF-8', false);
		$pdf->SetMargins(.5, .5);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor("Runner's Camp");
		$pdf->SetTitle("Roster" . ($camp ? ': ' . $camp : ''));

		// remove default header/footer
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// allow page breaking
		$pdf->SetAutoPageBreak(true, .5);

		// disable font subsetting
		$pdf->setFontSubsetting(false);

		// set font
		$pdf->SetFont('helvetica');

		// ---------------------------------------------------------

		// insert content
		$html = $this->get_roster_html($camp);
		foreach ($html as $page) {
			$pdf->AddPage();
			$page = preg_replace('~>\s*\n\s*<~', '><', $page); // remove whitespace
			$pdf->writeHTML($page);
		}


		while (ob_get_level()) {
			ob_end_clean();
		}

		// output pdf
		$filename = $camp
            ? "roster_" . strtolower(str_replace(" ","_",$camp))
            : "roster";
		$pdf->Output($filename . ".pdf");
	}

	function get_carpool_html($camp) {
		$registrations_list = new RC_Registrations_List();
		$registrations = $registrations_list->get_rc_registrations( ['camp' => $camp, 'order_by' => 'carpool']);

		ob_start();
		?>
        <style>
            td {
                height: 5.5in;
                width: 8.5in;
                text-align: center;
            }

            .title {
                font-size: 68pt;
                font-weight: bold;
                line-height: .9;
            }

            .subtitle {
                font-size: 34pt;
                line-height: 1;
            }
        </style>
        <table cellpadding="0" cellspacing="0">
			<?php
			foreach ($registrations as $registration) {
				$firstname = strtoupper( $this->get_meta($registration,"rc_firstname"));
				$lastname = strtoupper( $this->get_meta($registration,"rc_lastname"));
				$age = strtoupper($registration["age"]);
				$gender = strtoupper($registration["gender"]);
				?>
                <tr>
                    <td>
                        <div style="font-size: 80pt">&nbsp;</div>
                        <div class="title"><?php echo $lastname; ?></div>
                        <div class="subtitle"><?php echo $firstname; ?><br><?php echo $age; ?> YEAR OLD <?php echo $gender; ?></div>
                    </td>
                </tr>
			<?php } ?>
        </table>
		<?php
		return ob_get_clean();
	}

	function generate_carpool($camp) {

		// include the main TCPDF library
		require_once(RC_PATH . '/includes/admin-pages/includes/tcpdf/tcpdf.php');

		// create new PDF document
		//$pdf = new TCPDF('L', 'in', array('4.375', '3.25'), true, 'UTF-8', false);
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'in', 'LETTER', true, 'UTF-8', false);
		$pdf->SetMargins(0, 0);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor("Runner's Camp");
		$pdf->SetTitle("Carpool" . ($camp ? ': ' . $camp : ''));

		// remove default header/footer
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// allow page breaking
		$pdf->SetAutoPageBreak(true, 0);

		// disable font subsetting
		$pdf->setFontSubsetting(false);

		// set font
		$pdf->SetFont('helvetica');

		// ---------------------------------------------------------


		// insert content
		$pdf->AddPage();
		$html = $this->get_carpool_html($camp);
		$pdf->writeHTML($html);


		while (ob_get_level()) {
			ob_end_clean();
		}

		// output pdf
		$filename = $camp
            ? "carpool_" . strtolower(str_replace(" ","_",$camp))
            : "carpool";
		$pdf->Output($filename . ".pdf");
	}

	function get_parent_report_html($camp) {
		$RC_Registrations_List = new RC_Registrations_List;
		$registrations = $RC_Registrations_List->get_rc_registrations( ['camp' => $camp]);

		ob_start();
        ?>
        <style>
            table {
                border-collapse: collapse;
            }

            td {
                border: 1px solid #ddd;
                font-size: 9pt;
            }
        </style>

        <table cellpadding="3" cellspacing="0">
            <tr style="font-weight: bold">
                <td style="width: 1.5in">Camper</td>
                <td style="width: 3in">Parent/Guardian</td>
                <td style="width: 3in">Emergency Contact</td>
            </tr>
            <?php foreach ($registrations as $registration) :
            $work = $this->get_parent_work_phone($registration["order_id"]);
            $cell = $this->get_parent_cell($registration["order_id"]) ? : 'N/A';
            $phone = ($work == $cell)
                ? $work . " (work/cell)"
                : $work . " (work), " . $cell . " (cell)";
            ?>
            <tr>
                <td><?php
                echo $this->get_meta($registration,"rc_lastname") . ', ' . $this->get_meta($registration,"rc_firstname"); ?><br><?php
                echo $registration["gender"] . ', ' . $registration["age"]; ?>
                </td>
                <td><?php
                echo $this->get_parent_last_name($registration["order_id"]) . ', ' . $this->get_parent_first_name($registration["order_id"]); ?><br><?php
                echo $phone; ?><br><?php
                echo $this->get_parent_email($registration["order_id"]); ?>
                </td>
                <td><?php
                echo $this->get_emergency_contact($registration["order_id"]); ?><br>Relationship: <?php
                echo $this->get_emergency_contact_relationship($registration["order_id"]); ?><br>Phone: <?php
                echo $this->get_emergency_phone($registration["order_id"]); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
        return ob_get_clean();
	}

	function generate_parent_report($camp) {

		// include the main TCPDF library
		require_once(RC_PATH . '/includes/admin-pages/includes/tcpdf/tcpdf.php');

		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'in', 'LETTER', true, 'UTF-8', false);
		$pdf->SetMargins(.5, .5);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor("Runner's Camp");
		$pdf->SetTitle("Parent/Guardian Report" . ($camp ? ': ' . $camp : ''));

		// remove default header/footer
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// allow page breaking
		$pdf->SetAutoPageBreak(true, .5);

		// disable font subsetting
		$pdf->setFontSubsetting(false);

		// set font
		$pdf->SetFont('helvetica');

		// ---------------------------------------------------------

		// insert content
		$pdf->AddPage();
		$html = $this->get_parent_report_html($camp);
		$pdf->writeHTML($html);

		while (ob_get_level()) {
			ob_end_clean();
		}

		// output pdf
		$filename = $camp
			? "parent_report_" . strtolower(str_replace(" ","_",$camp))
			: "parent_report";
		$pdf->Output($filename . ".pdf");
	}
}
