<?php
/*
 Hacemos referencia al archivo wp-blog-header.php para
 que nuestro archivo incluya la funcionalidad de Wordpress
*/
require_once( dirname(__FILE__) . '/wp-blog-header.php' );

ini_set('display_errors', 1);
ini_set('max_execution_time', 10 * 3600);
ini_set('memory_limit', '16384M');


// Get data
if (isset($_GET['numPoints'])) {
    $numPoints = $_GET["numPoints"];
}

if (isset($_GET['id_producto'])) {
    $post_id = $_GET['id_producto'];
}

$MAX_CUPONES_PLATA=1;
$MAX_CUPONES_ORO=2;
$MAX_CUPONES_DIAMANTES=4;

$isDebug = false;
////afsanchezb@yahoo.es -> 41626
 if (get_current_user_id() == 1) $isDebug = true;
////

////cargarovi@gmail.com -> 5644
// if (get_current_user_id() == 5644) $isDebug = true;
////

$points_title='answer-cupones';
$points_descrip='Puntos usados en cupones';

$errorSelectedCupon = '/club/canjea-puntos/cupones/error-data-in'; //si no tengo el id_cupon/higthcode del cupon o los puntos a descontar
$successPoints = '/club/canjea-puntos/cupones/msg-ok-cupon'; //se ha podido solicitar el cupon
$errorMaxUsedCupones = '/club/canjea-puntos/cupones/msg-error-cupon-excedido'; //ya ha usado el maximo de cupones permitido
$errorMaxSpecialUsedCupones = '/club/canjea-puntos/cupones/msg-error-numero-de-cupones-especiales-excedido'; //ya ha usado el maximo de cupones especiales permitido
$errorRankCupon = '/club/canjea-puntos/cupones/error-categoria-insuficiente'; //no tiene rango suficiente para seleccionar este cupon
$errorPoints = '/club/canjea-puntos/cupones/error-puntos-insuficientes'; //No tiene puntos suficientes para seleccionar este cupon
$usuaDescon = '/club/canjea-puntos/cupones/error-campos-usuario'; //El usuario no tiene los campos de nombre y apellidos rellenados

$errorNoSendCupon = '/club/canjea-puntos/cupones/error-correo'; //no se ha podido enviar el correo, no debería pasar por aquí

$hrefLogin = '/club/mi-cuenta/';
$imgProduct="";

/** creamos el correo de los cupones de denatada **/
function getHtmlCupon($email, $name,$infoTitleProduct,$imgProduct) {
    //el enlace tiene la forma "http://195.200.165.154/Pixiboxmasterprint.aspx?Email='.$_REQUEST['email'].'&partnerKey=7A087850A0"
    //Aquí­ generamos el cuerpo del email que se le va a mandar al usuario,
    //y dentro va un boton al enlace donde podra imprimir el cuponn
    $strHtml='<html>

        <body>
            <div style="text-align:center;font-family:Arial, Helvetica, sans-serif; border-right: 4px solid #fedc84; border-left: 4px solid #fedc84; border-bottom: 4px solid #fedc84; width: 600px; margin:0 auto;">
             <div style="background-color: #fedc84; padding:20px 15px 20px 15px;">
                <h2 style="text-align: center; font-family: \'Passion One\'; font-size:48px; line-height:0.8em; color:#00bebe">¡Imprime tu descuento!</h2>
                <p style="text-align: center; font-family: \'Passion One\'; color:#00bebe; font-size: 20px;">Estás a punto de conseguir tu cupón. Asegúrate de que tu impresora está encendida y de que tiene papel. Una vez hagas click en el botón "Imprimir" no habrá vuelta atrás.</p>
            </div>
            <div style="padding:40px;">

                <a style="color: #ffffff;font-size: 16px; font-family: Arial, Helvetica, sans-serif; letter-spacing: 1px; background: #ff5d82; border-radius: 27px; padding: 10px 20px 10px 20px; text-decoration: none;" href="http://195.200.165.154/Pixiboxmasterprint.aspx?Email='.$email.'&partnerKey=38E7FE99D7">Imprimir</a>
            </div>
            <div style="padding:20px 15px 20px 15px;">
                <p style="text-align: center; font-family: \'Passion One\', sans-serif; font-size: 11px;">De acuerdo al Reglamento (UE) 2016/679 del Parlamento Europeo y del Consejo de 27 de abril de 2016 relativo a la protección de las personas físicas en lo que respecta al tratamiento de datos personales y a la libre circulación de estos datos, le informamos que Ud. recibe la presente comunicación con la finalidad de envío de comunicaciones comerciales de los productos y promociones de Central Lechera Asturiana, asociados a su dirección de email. Puede consultar toda la información referente al tratamiento de sus datos en la siguiente página web: <a style="color:#333" href="https://www.centrallecheraasturiana.es/es/politica-de-privacidad">https://www.centrallecheraasturiana.es/es/politica-de-privacidad</a>. Puede cancelar su suscripción o dar de baja su cuenta en el enlace que verá a continuación: <a style="color:#333" href="https://www.centrallecheraasturiana.es/club/mi-cuenta/" target="_blank">https://www.centrallecheraasturiana.es/club/mi-cuenta/</a>.</p>
                <p style="text-align: center; font-family: \'Passion One\', sans-serif; font-size: 12px;">© 2023 CAPSA</p>
            </div>
        </div>
        </body>
        </html>';
    return $strHtml;
}
//https://www.centrallecheraasturiana.es/wp-content/uploads/2018/02/cupon-desntadas-mailing.jpg

/** Enviamos el correo de los cupones de denatada **/
function enviarCupon($email, $name, $infoTitleProduct,$imgProduct) {
    $strHtmlCupon = getHtmlCupon($email, $name, $infoTitleProduct, $imgProduct);
    $headers = array('Content-Type: text/html; charset=UTF-8');
    //send coupon
    $isSend = wp_mail($email,"Promoción Club Central Lechera Asturiana: {$infoTitleProduct}", $strHtmlCupon, $headers);
    //,['From: Central Lechera Asturiana <no-reply@centrallecheraasturiana.es>']
    return $isSend;
}

function cuponInfluencers($fisrtName, $lastName, $email, $codeHighco) {
    $strResult='';
    try {
    	// new resource
//        $client = new SoapClient('https://soap.pixiboxwserv.fr/PixiboxInt_Dev.asmx?wsdl', array("soap_version" => SOAP_1_2));
        $client = new SoapClient('http://195.200.165.154/Services/PixiboxInt_Dev.asmx?wsdl', array("soap_version" => SOAP_1_2));
        //Prepare data in order to send it to the webservice
        $userData =array(
            /*'PartnerKey'=>utf8_encode('62A7D4FC42'),            //Nos lo pasan ellos, siempre es el mismo
            'PartnerPassword'=>utf8_encode('ZgFuBkQmPg'),       //Nos lo pasan ellos, siempre es el mismo
            'PartnerUID'=> 160,*/
            'PartnerKey'=>utf8_encode('38E7FE99D7'),            //Nos lo pasan ellos, siempre es el mismo
            'PartnerPassword'=>utf8_encode('AqMkCuRzGp'),       //Nos lo pasan ellos, siempre es el mismo
            'PartnerUID'=> 183,                             //Nos lo pasan ellos, siempre es el mismo
            'FirstName'=>utf8_encode($fisrtName),      //Nombre del usuario del Club, o de la persona que va a solicitar el cupÃ³n
            'LastName'=>utf8_encode($lastName),    //Apellidos del usuario del Club, o de la persona que va a solicitar el cupÃ³n
            'Language'=>5,                                      //Nos lo pasan ellos, siempre es el mismo
            'AddressPostalCode'=>utf8_encode('N/A'),            //Siempre va vacÃ­o
            'AddressCity'=>utf8_encode('N/A'),                  //Siempre va vacÃ­o
            'CouponsList'=>utf8_encode($codeHighco),//3993--3507          //Los ids del listado de cupones que se van a generar. Puede ser el mismo varias veces o distintos cupones.
            'URN'=>utf8_encode($email),             //Email del usuario del Club, o de la persona que va a solicitar el cupÃ³n
            'customProperty1'=>utf8_encode('test'),                 //VacÃ­o
            'customProperty2'=>utf8_encode('test')                  //VacÃ­o
        );

        //Call the webservice
	    $reply = $client->CreateUpdateUserResaMail($userData);
	    $strResult=$reply->CreateUpdateUserResaMailResult;
        var_dump ($userData);
        echo '<br>';
        var_dump ($reply);


	    // new resource
//        $reply = $client->CreateUpdateUser($userData);
//        $strResult=$reply->CreateUpdateUserResult;

    }catch (Exception $e){
            //http://195.200.165.154/Services/PixiboxInt_Dev.asmx?wsdl
        die($e->getMessage());
    }

	$pos = strpos($strResult, 'NOOK_Result');
	if ($pos !== false) {
		//Limit has been exceeded
		return false;
	}
	$pos = strpos($strResult, 'OK_Result');
	if ($pos !== false) {
		//Can send coupon
		return true;
	}
	// new resource
//    if($strResult === 1 || $strResult ===2){
//        return true;
//    }
//    return false;


}

add_action( 'woocommerce_checkout_process', 'incluirCuponEnPedidos',  1, 1  );
function incluirCuponEnPedidos( $order_id ) {

  global $woocommerce;
  //Create order
  $order = wc_create_order();
  //Add product to order
  $order->add_product( get_product($order_id), 1);
  $order->calculate_totals();
  //Set order status as completed
  $order->update_status("completed", 'CUPON ADQUIRIDO', TRUE);
  //Asign order to current loged user
  update_post_meta( $order->get_order_number(), '_customer_user', get_current_user_id() );

}

// Only usable for members
if (is_user_logged_in()) {
    $cu = wp_get_current_user();

    global $wpdb;
    $strRankTitleProduct = '';
    $strRankCodeProduct = '';
    $codeHighco = 0;
    //Get current user rank
    $rank_user = mycred_get_my_rank();
    //Get user current available points
    $currentPoints = mycred_get_account( $cu->ID );
    //Si el usuario no tiene un nombre o apellido en la info_extra-lo redirigimos
    $infoUserNombre = $wpdb->get_var( "
    SELECT nombre
    FROM wp_info_user_extra
    WHERE user_id = {$cu->ID}");
    var_dump($infoUserNombre);
    if( is_null($infoUserNombre["nombre"])){
        echo "Pruebas";
        header("Location: ".$usuaDescon);
        var_dump($infoUserNombre->nombre);
        exit;
    }
    if ( !empty( $post_id )) {
        $infoTitleProduct = $wpdb->get_var( "
                    SELECT post_title
                    FROM {$wpdb->posts}
                    WHERE ID = {$post_id}");
        $imgProduct= wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'single-post-thumbnail' );
        //Get product to know how many points it costs
        $infoPointProduct = $wpdb->get_var( "
                    SELECT meta_value
                    FROM {$wpdb->postmeta}
                    WHERE post_id = {$post_id} and meta_key ='_custom_mycred_price'");

        $myCRED_Balance = $currentPoints->balance;
        if ($myCRED_Balance['mycred_default']->current < $infoPointProduct) {
        //User has not enough points
            if ($isDebug) {
                echo "<p>No tienes suficientes puntos para poder SOLICITAR UN CUPON</p>";
            } else {
                header("Location: ".$errorPoints);
                exit;
            }
        } else {
            //Get minimum rank to get coupon
            $metaValue = '_minimal_mycred_level';
            $meta_posts = $wpdb->get_results("SELECT post_id, meta_value, meta_key FROM wp_postmeta WHERE post_id = {$post_id}  and meta_key ='{$metaValue}' ") ; //
            //Get possible ranks
            if (function_exists('mycred_get_ranks')) {
                $all_ranks = mycred_get_ranks( 'publish', '-1', 'ASC', MYCRED_DEFAULT_TYPE_KEY );
                if ( ! empty( $all_ranks ) ) {
                    foreach ( $all_ranks as $rank ) {
                        if ($rank->post_id == $meta_posts[0]->meta_value) {
                            $strRankProduct= $rank->title;
                            $strRankCodeProduct = $rank->post_id;
                        }

                    }
                    }
            }
            //Get coupon rank
            $metaValueCodeHighco = '_coupon_highco_code';
            $codeHighco = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE post_id = {$post_id}  and meta_key ='{$metaValueCodeHighco}' ") ; //
            if ($codeHighco != NULL) {
                if ($rank_user->post_id >= $strRankCodeProduct) {
                    //Let user to get the coupon
                    $isCupones=false;
                    if ($infoPointProduct != '' ) {
                        //If not empty fields
                        //Get used coupons by this user in the current month

                        
                        $monthActual= date('n');
                        $yearActual= date('Y');

                        $infoUserCupones = $wpdb->get_results( "
                            SELECT num_cupones, creation_date
                            FROM wp_info_user_cupones
                            WHERE id_user = {$cu->ID}
                            AND MONTH(creation_date) = {$monthActual} AND YEAR(creation_date) = {$yearActual} ");
                        //echo "<p>1: {$rank_user->title} </p>";
                        if ($infoUserCupones != null) {
                        //There are info this month
                        //Check if user has coupons available
                            if ($rank_user->title == 'Basico') {
                                //No coupon for you
                                $isCupones = false;
                            }else if ($rank_user->title == 'Plata') {
                                //1 coupon by month
                                //Check if user has used his coupon
                                if ($infoUserCupones[0]->num_cupones < $MAX_CUPONES_PLATA) {
                                    $isCupones = true;
                                }
                            }else if ($rank_user->title == 'Oro') {
                                //2 coupons by month
                                //Check if user has used their coupons
                                if ($infoUserCupones[0]->num_cupones < $MAX_CUPONES_ORO) {
                                    $isCupones = true;
                                }
                            }else if ($rank_user->title == 'Diamante') {
                                //4 coupons by month
                                //Check if user has used their coupons
                                if ($infoUserCupones[0]->num_cupones < $MAX_CUPONES_DIAMANTES) {
                                    $isCupones = true;
                                }
                            }
                        } else {
                        //There aren't info this month
                            if ($rank_user->title == 'Basico') {
                                //no damos cupon
                                $isCupones = false;
                            } else {
                                $isCupones = true;
                            }
                        }

                        $isSpecialCoupon = get_post_meta( $post_id, 'es_cupon_especial', true );
                        //If current coupon is special and user can get coupons
                        if ($isSpecialCoupon && $isCupones) {
                            //Check if user has used this special coupon
                            $specialCoupon = get_user_meta( $cu->ID, 'special_coupon_' . $post_id , true );
                            if (!empty( $specialCoupon )) {
                                // do stuff
                                if ($specialCoupon == date('Ym')) {
                                    //User used current month special coupon
                                    //No coupon for you
                                    if ($isDebug) {
                                        echo "<p>Ya ha usado el número maximo de cupones especiales.</p>";
                                    } else {
                                        header("Location: ".$errorMaxSpecialUsedCupones);
                                        exit;
                                    }
                                }
                            }
                        }
                        if ($isCupones) {
                            //If user can get coupons


                            $lastName = $cu->user_lastname;
                            if (empty($cu->user_lastname)) {
                                $lastName = $cu->user_firstname;
                            }

                            if ($isDebug) {
                                echo "<p>*******LLAMAMOS -0 A EMPRESA CUPON</p>";
                            }

                            //Create coupon
                            $hayCupon = cuponInfluencers($cu->user_firstname, $lastName, $cu->user_email, $codeHighco);

                            if ($isDebug) {
                                echo "<p>*******LLAMAMOS -2 A EMPRESA CUPON</p>".$hayCupon;
                                echo "<p>*******LLAMAMOS -2 A EMPRESA CUPON</p>".$codeHighco;
                                var_dump ($hayCupon);

                            }

                            if ($hayCupon) {
                               //Generate html to send coupon mail
                                $isSend= enviarCupon($cu->user_email,$cu->user_firstname, $infoTitleProduct, $imgProduct);
                                if ($isSend) {
                                    //If coupon is sent and is special coupon
                                    if ($isSpecialCoupon) {
                                        //Save that current year month user has used special coupon
                                        update_user_meta( $cu->ID, 'special_coupon_' . $post_id, date('Ym') );
                                    }




                                    //Actualizamos los cupones del pavo
                                    //Add 1 to current used coupons (this month)
                                    $cuponesActuals = 0;
                                    if ( !empty( $infoUserCupones[0]->num_cupones ) ) {
                                        $cuponesActuals = $infoUserCupones[0]->num_cupones;
                                    }
                                    $cuponesActuals = $cuponesActuals + 1;
                                    //echo "<p>4: {$cuponesActuals}</p>" ;

                                    //Save that user has gotten a coupon
                                    if ($infoUserCupones == null) {
                                    //If no info about current month
                                        $wpdb->insert(
                                            'wp_info_user_cupones',
                                            array(
                                                'id_user' => $cu->ID,
                                                'num_cupones' => $cuponesActuals,
                                                'creation_date' => date("Y-m-d H:i:s")
                                            ),
                                            array(
                                                '%d',
                                                '%s',
                                                '%s'
                                            )
                                        );
                                        $insert_id = $wpdb->insert_id;

                                    } else {
                                    //If we have info about current month
                                        $wpdb->update(
                                            'wp_info_user_cupones',
                                            array(
                                                'num_cupones' => $cuponesActuals,
                                                'creation_date' => date("Y-m-d")
                                            ),
                                            array( 'id_user' => $cu->ID , 'creation_date' => $infoUserCupones[0]->creation_date ),
                                            array(
                                                '%d',    // value1
                                                '%s'    // value2
                                            ),
                                            array( '%d','%s' )
                                        );
                                        if ($isDebug) {
                                            echo "<p>Se ha actualizado nueva información extendida del usuario con id: {$cu->ID}</p>" ;
                                            $infoUserCupones = $wpdb->get_results( "
                                                SELECT num_cupones, creation_date
                                                FROM wp_info_user_cupones
                                                WHERE id_user = {$cu->ID}
                                                AND MONTH(creation_date) = {$monthActual} AND YEAR(creation_date) = {$yearActual} ");

                                            print_r($infoUserCupones);
                                        }
                                    }




                                    //Reduce user points
                                    mycred_add($points_title , $cu->ID, -$infoPointProduct,  $points_descrip);
                                    if ($isDebug) {
                                        echo "<p>Cupones cajeados.</p>";
                                    } else {
                                        do_action( 'woocommerce_checkout_process', $post_id, 1, 1  );
                                        header("Location: ".$successPoints);
                                        exit;
                                    }
                                } else {
                                    if ($isDebug) {
                                        echo "<p>No se ha podido enviar el correo.</p>";
                                    } else {
                                        header("Location: ".$errorNoSendCupon);
                                        exit;
                                    }
                                }
                                } else {
                                //Can't create coupons, exceeded coupon total limit.
                                    if ($isDebug) {
                                        echo "<p>No hay cupon Excedido el numero maximo de cupones existentes.</p>";
                                    } else {
                                        header("Location: ".$errorMaxUsedCupones);
                                        exit;
                                    }
                            }

                        } else {
                        //Can't create coupons, user exceeded coupon limit for this month
                            if ($isDebug) {
                                echo "<p>Ya ha usado el número maximo de cupones.</p>";
                            } else {
                                header("Location: ".$errorMaxUsedCupones);
                                exit;
                            }
                        }

                    } else {
                        //Coupon has not configured points value
                        if ($isDebug) {
                            echo "<p>los puntos estarían vacíos y no se debería dar el caso</p>";
                        } else {
                            header("Location: ".$errorSelectedCupon);
                            exit;
                        }
                    }

                } else {
                    //not enough rank for this coupon
                    if ($isDebug) {
                        echo "<p>No tiene rango suficiente para poder comprar cupon</p>";
                    } else {
                        header("Location: ".$errorRankCupon);
                        exit;
                    }
                }
            } else {
                //Coupon has not configured HighCo code
                if ($isDebug) {
                    echo "<p>NO TENEMOS -CODE HIGHCO-</p>";
                } else {
                    header("Location: ".$errorSelectedCupon);
                    exit;
                }
            }
        }
    } else {
        //There is not a coupon selected
        if ($isDebug) {
            echo "<p>No hay ningun cupon seleccionado</p>";
        } else {
            header("Location: ".$errorSelectedCupon);
            exit;
        }
    }

} else {
    if ($isDebug) {
        echo "<p>Usuario no logueado</p>";
    } else {
        header("Location: ".$hrefLogin);
        exit;
    }
}

 
  
  
