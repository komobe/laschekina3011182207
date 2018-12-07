<?php 

namespace LSI\MarketBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class BaseController extends Controller
{

	    /**
     * @author Moro KONE
     * @param $private_key
     * @param $str_to_crypt
     * @return string
     */
    protected function crypt($private_key, $str_to_crypt) {
        $private_key = md5($private_key);
        $letter = -1;
        $new_str = '';
        $strlen = strlen($str_to_crypt);

        for ($i = 0; $i < $strlen; $i++) {
            $letter++;
            if ($letter > 31) {
                $letter = 0;
            }
            $neword = ord($str_to_crypt{$i}) + ord($private_key{$letter});
            if ($neword > 255) {
                $neword -= 256;
            }
            $new_str .= chr($neword);
        }
        return base64_encode($new_str);
    }

    /**
     * @author Moro KONE
     * @param $private_key
     * @param $str_to_decrypt
     * @return string
     */
    protected function decrypt($private_key, $str_to_decrypt) {
        $private_key = md5($private_key);
        $letter = -1;
        $new_str = '';
        $str_to_decrypt = base64_decode($str_to_decrypt);
        $strlen = strlen($str_to_decrypt);
        for ($i = 0; $i < $strlen; $i++) {
            $letter++;
            if ($letter > 31) {
                $letter = 0;
            }
            $neword = ord($str_to_decrypt{$i}) - ord($private_key{$letter});
            if ($neword < 1) {
                $neword += 256;
            }
            $new_str .= chr($neword);
        }
        return $new_str;
    }


    /**
     * @param $response
     * @param $datedebut
     * @param $datefin
     * @return Response
     */
    protected function setRechercheIndexCookie($response,$datedebut, $datefin)
    {

        $crypt = $this->getRechercheIndexCookieKey();
        $dates =['debut' =>$datedebut , 'fin' => $datefin] ;
        $content = serialize($dates);
        $cookie = new Cookie($crypt, base64_encode($content), time() + 86400);
        //$response = new Response();
        $response->headers->setCookie($cookie);
        return $response;
    }

    /**
     * @param Request $request
     * @return array|null
     */
    protected function getRechercheIndexCookie(Request $request)
    {
        $crypt = $this->getRechercheIndexCookieKey();

        if ($date = $request->cookies->get($crypt)) {
            return unserialize(base64_decode($date));
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getRechercheIndexCookieKey()
    {
        $private_key = 'marketcookiedate'; // Paramétrage de la clé de cryptage du cookie

        $crypt = $this->crypt($private_key, "indexgetrePchercKhe@#c@okie{+%ZT$");
        return $crypt;
    }


}