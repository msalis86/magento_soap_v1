<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/**
 * Php Magento soap library
 *
 * Libreria che permette di di implemetare una connessione soap con magento e ne permette la creazioene , aggiornamento di un cliente.
 *
 * @category        Libraries
 * @author          Marco Salis
 * @link            https://github.com/msalis86/magento_soap_v1
 * @license         https://github.com/msalis86/magento_soap_v1/blob/master/LICENSE
 */

class Magento_soap {
    
    public $api_user;
    public $apikey;
    public $base_url_soap;
    public $client;
    public $session;
    public $info_customer;
    public $customer_id;
    public $chiave_autologin;
    
    
    public function __construct($param){
                                         
        if(isset($param['api_user'])){
            $this->api_user = $param['api_user'];     
        }
        
        if(isset($param['apikey'])){
            $this->apikey = $param['apikey'];     
        }
        
        if(isset($param['base_url_soap'])){
            $this->base_url_soap = $param['base_url_soap'];     
        }
        
        $this->info_customer = array( array() );
        $this->customer_id = null ;
                
                                    
        $this->createClient();
        $this->login();               
        
    }
    /**
     * Metodo che permette di effettuare la creazione del client soap per poi poter effettuare le chiamate ai vari metodi soap 
     * @return bool true in caso di successo. 
     */
     
    private function createClient(){
        if(isset($this->base_url_soap)) {
            //$wsdl = trim(file_get_contents($this->base_url_soap));
            $this->client = new SoapClient($this->base_url_soap, $options = array( 'cache_wsdl' => 0, ) );           
                        
        } else {
            throw new Exception("Error createClient. base_url_soap not is set", 1);
        }
        
        return true;
        
    }
    
    /**
     * Metodo che crea una sessione autenticata per effettuare le chiamate al webservice soap(v1) di Magento
     * @return bool true in caso di successo. 
    */
    
    private function login(){
        if(isset($this->api_user) && isset($this->apikey)) {
                       
            $this->session = $this->client->login($this->api_user, $this->apikey);                
        } else {
            throw new Exception("Error loginMagento . api_user or apikey not are set", 1);
        }
         
        return true;
    }
    
    /**
     * Metodo che restuisce, se presente, il customer_id di un cliente 
     * @param string $email : (required) email valida per ricercare il cliente  
     * @return int in caso il cliente sia presente atrimenti null. 
    */    
    public function getUserByEmail($email = null){
         if(isset($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== FALSE ){
             
         
             $filter = array ( array(
                                'email' =>   array('eq' => $email) 
                                )
                        );
                        
             $response = $this->client->call($this->session, 'customer.list', $filter);
             
             //var_dump("<br>getUserByEmail response <br>",$response);
             if(isset($response[0], $response[0]['customer_id']) && is_numeric( $response[0]['customer_id']) ){
                 $this->customer_id = $response [0]['customer_id'];
             }else{
                 $this->customer_id = null;
             }
             //var_dump('getUserByEmail $this->customer_id ',$this->customer_id);
        } else{
             throw new Exception("Error getUSerByEmail . Email not valid ", 1);         
        }  
                    
         return $this->customer_id;        
    }
    
    /**
     * Metodo crea un cliente  
     * @param array  $info_customer : (required) array(array()) contenente almeno le seguenti campi:    
     *              "email" : string email valida,         
     *              "firstname" => string nome, 
     *              "lastname"=> string cognome
     *              per maggiorni informazioni sui vari campi visitare http://www.magentocommerce.com/api/soap/customer/customer.html                                            
     * @return int in caso in caso di creazione corretta del cliente 
    */ 
    
    public function createUser( $info_customer = null ){
        if(isset($info_customer)){
            
            $response = $this->client->call($this->session,'customer.create',$info_customer);
            
            if(is_numeric($response)){
                $this->customer_id = $response; 
            }
            
            //var_dump("<br>createUser response <br>",$response, $this->customer_id);
        }else{
            throw new Exception("Error createMagentoUser . info_customer not valid ", 1);    
        }
        
        return $response;
    }
    
    /**
     * Metodo per la creazione di un cliente 
     * @param array $info_customer: (required) array(array()) contenente la lista dei campi da aggiornare          
     *              per maggiorni informazioni sui vari campi visitare http://www.magentocommerce.com/api/soap/customer/customer.update.html   
     *        int $customer_id : (optional) intero contente il customre_id di un cliente. Non obbligatorio se prima si è richiamto il metodo getUserByEmail                                          
     * @return int in caso in caso di creazione corretta del cliente 
    */ 
    
    public function UpdateUser( $info_customer = null, $customer_id =  NULL ){
        if(isset($info_customer[0]) && is_array($info_customer[0]) ){            
            if(isset($customer_id)){
                $this->customer_id = $customer_id; 
                
                
            } else if ( !isset($this->customer_id ) ) {
                throw new Exception("Error UpdateMagentoUser . Magento customer_id  not isset. Run method getUSerByEmail or put customer_id parameter  ", 1); 
            }   
            
            $response = $this->client->call($this->session,'customer.update',array('customerId'=> $this->customer_id ,'     customerData' => $info_customer[0] ));    
        }else{
            throw new Exception("Error UpdateMagentoUser . info_customer not valid " , 1);
        }

        return $response;
    }
    
    /**
     * Metodo per la creazione di un indirizzo associato al cliente  
     * @param array $info_address: (required) array contenente almeno le seguenti campi:    
     *                  'country_id'
     *                  'firstname'
     *                  'lastname'
     *                  'city'
     *                  'region'
     *                  'postcode'
     *                  'street'
     *                  'region_id' 
     *                  'telephone'         
     *              per maggiorni informazioni sui vari campi visitare http://www.magentocommerce.com/api/soap/customer/customer.update.html   
     *        int $customer_id : (optional) intero contente il customre_id di un cliente. Non obbligatorio se prima si è richiamto il metodo getUserByEmail                                          
     * @return int in caso in caso di creazione corretta del indirizzo 
    */ 

    function CreateAddress($info_address = NULL, $customerId = NULL){
       
        if(isset($customer_id)) {
            $this->customer_id = $customer_id; 
                               
        } else if ( !isset($this->customer_id ) ) {
            throw new Exception("Error CreateAdress . Magento customer_id not isset. Run method getUSerByEmail or put customer_id parameter  ", 1); 
        }  
                            
        if( isset(  $info_address ,
                    $info_address['country_id'],
                    $info_address['firstname'],
                    $info_address['lastname'],
                    $info_address['city'],
                    $info_address['region'],
                    $info_address['postcode'],
                    $info_address['street'],
                    $info_address['region_id'], 
                    $info_address['telephone'] )                     
        ) {
            $response = $this->client->call( 
                $this->session ,
                'customer_address.create',
                array('customerId' => $this->customer_id, 'addressdata' => $info_address ) );
       
            return $response;
                        
        }else{           
            throw new Exception("Error CreateAdress . $info_address not valid " , 1);
        }        

        return NULL;
    }

    
}
