<?php

namespace DBModules\Auth;

use DBSnoop\Annotations\Auth;
use DBSnoop\Annotations\Needed;
use DBSnoop\Annotations\Request;
use DBSnoop\Annotations\Route;
use DBSnoop\Annotations\Type;
use DBSnoop\Entity\Customer;
use DBSnoop\Entity\User;
use DBSnoop\System\Response;
use DBSnoop\System\ServerRequestControl;
use DBSnoop\Extension\Customer as ExtensionCustomer;
use DBSnoop\Controller\Email;
use React\Dns\Resolver\Resolver;
use DBSnoop\Extension\User as EUser;

class Register extends ServerRequestControl
{
    /**
     *
     * @Route("/auth/temp_register")
     * @Auth(false)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "user_name",
     * "customer_name",
     * "email",
     * "company_code",
     * "country",
     * "phone",
     * "language"
     * })
     */
    public function temp_register(): Response\JSON
    {
        

        try {
        
        $email = new Email();
        $user = new User();
        $user->name = $this->REQUEST['user_name'];
        $user->email = $this->REQUEST['email'];
        $user->telephone = $this->REQUEST['phone'];
        $user->preferred_language = $this->REQUEST['language'];

        $customer = new Customer();
        $customer->name = $this->REQUEST['customer_name'];
        $customer->company_code = $this->REQUEST['company_code'];
        $customer->country = $this->REQUEST['country'];
        $valid_extension_customer = ExtensionCustomer::createTemporaryCustomer($customer,$user);

        if(!$valid_extension_customer){
            return new Response\JSON("error", "ERROR_CREAT_USER");
        }

        $email->addAddress($this->REQUEST['email'], $this->REQUEST['user_name']);
    
        $email->Subject = "Cadastro DBSnoop";

        $email->Body = "Código de validação DBSnoop: " . $valid_extension_customer['validate_code'];

        $email->Send();
        
        //var_dump(ExtensionCustomer::createTemporaryCustomer($customer,$user)['validate_code']);

            return new Response\JSON("ok", $valid_extension_customer['access_hash']);
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("error", $th->getMessage());
        }catch(\PHPMailer\PHPMailer\Exception $e){
            return new Response\JSON("error", $e->getMessage());
        }catch(\Exception $e){
            return new Response\JSON("error", $e->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }


    /**
     *
     * @Route("/auth/valid_temp_register")
     * @Auth(false)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "validate_code",
     * "access_hash"
     * })
     */
    public function valid_temp_register(): Response\JSON
    {
        

        try {
            $valid = ExtensionCustomer::validateHashTempCustomer($this->REQUEST['access_hash'], $this->REQUEST['validate_code']);
            if($valid['status'] != 'ok'){
                return new Response\JSON("error", $valid['msg']);
            }

            return new Response\JSON("ok", "ok");
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("error", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }

    /**
     *
     * @Route("/auth/register_subscription")
     * @Auth(false)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "access_hash",
     * "subscription_id"
     * })
     */
    public function register_subscription(): Response\JSON
    {
        try {
            $valid_access_hash = ExtensionCustomer::registerSubscriptionCustomer($this->REQUEST['access_hash'], $this->REQUEST['subscription_id']);
            if($valid_access_hash['status'] === 'error'){
                return new Response\JSON("error", $valid_access_hash['msg']);
            }

            return new Response\JSON("ok", "ok");
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("error", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("ok", $th->getMessage());
        }

    }


    /**
     *
     * @Route("/auth/reupper_register")
     * @Auth(false)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "email"
     * })
     */
    public function reupper_register(): Response\JSON
    {

        // $value_extension = ExtensionCustomer::reupperTempRegisterCache($this->REQUEST['email']);
        $value_extension = EUser::loadUserByEmail($this->REQUEST['email']);

        if($value_extension){
            $email = ExtensionCustomer::reupperTempRegisterCache($value_extension);
        }else{
            return new Response\JSON("error", "INVALID_EMAIL");
        }

        try {
            
            return new Response\JSON("ok", $email['access_hash']);
        } catch (\Exception $th) {
            //var_dump($th->getPrevious());
            return new Response\JSON("error", $th->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }



    /**
     *
     * @Route("/auth/resend_validation_code")
     * @Auth(false)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "access_hash"
     * })
     */
    public function resend_validation_code(): Response\JSON
    {   
        try {
            $value_extension = ExtensionCustomer::getTempRegisterInformation($this->REQUEST['access_hash']);
            $get_value = $value_extension['user'];
            $email = new Email();

            if($value_extension['status'] != 'error'){
                $email->addAddress($get_value->getEmail(), $get_value->getName());
                $email->Subject = "Cadastro DBSnoop";
                $email->Body = "Código de validação DBSnoop: " . $value_extension['validate_code'];
                $email->Send();
                return new Response\JSON("ok", "ok");
            }else{
                return new Response\JSON("error", "INVALID_ACCESS_HASH");
            }
        } catch (\Exception $th) {
            return new Response\JSON("error", $th->getMessage());
        }catch(\PHPMailer\PHPMailer\Exception $e){
            return new Response\JSON("error", $e->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }

    /**
     *
     * @Route("/auth/valid_access_hash")
     * @Auth(false)
     * @Type("JSON")
     * @Request("POST")
     * @Needed({
     * "access_hash"
     * })
     */
    public function valid_access_hash(): Response\JSON
    {   
        try {
            $value_extension = ExtensionCustomer::getTempRegisterInformation($this->REQUEST['access_hash']);

            if($value_extension['status'] != 'error'){
                return new Response\JSON("ok", "VALID_HASH");
            }
            return new Response\JSON("error", "INVALID_ACCESS_HASH");
            
        } catch (\Exception $th) {
            return new Response\JSON("error", $th->getMessage());
        }catch(\PHPMailer\PHPMailer\Exception $e){
            return new Response\JSON("error", $e->getMessage());
        } catch (\Throwable $th) {
            return new Response\JSON("error", $th->getMessage());
        }

    }


}
