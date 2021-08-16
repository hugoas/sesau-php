<?php

    App::uses('CakeEmail', 'Network/Email');
    
    class FaleConoscoController extends AppController {
        
        var $name = 'FaleConosco';
        public $uses = array('ConsultaPublica', 'Legislacao', 'Boletim');
        
        public $components = array('Util');
        
        function beforeFilter(){
            parent::beforeFilter();
            $this->Auth->allow('index', 'enviar', 'captcha');
        } 
        
        public function index() {
            $consultasPublica = $this->ConsultaPublica->find('all', array('limit'=>'5','order' => array('ConsultaPublica.codigo' => 'DESC')));
            $this->set('consultasPublica', $consultasPublica);

            $legislacoes = $this->Legislacao->find('all', array('limit'=>'5','order' => array('Legislacao.codigo' => 'DESC')));
            $this->set('legislacoes', $legislacoes);

            $boletins = $this->Boletim->find('all', array('limit'=>'5','order' => array('Boletim.codigo' => 'DESC')));
            $this->set('boletins', $boletins);
            $this->set('area', parent::CIDADAO);
            parent::carregarDadosBarraCidadao();
        }
        
        public function enviar(){
            if(!empty($this->data)){
                if( !$this->Util->validaEmail($this->data['FaleConosco']['email'])) {
                    $this->Session->setFlash(__('Email inválido'), 'erro');
                } else {
                    try {
                        
                        $captchaSessao = strtoupper($this->Session->read("captcha"));
                        $captchaUsuario = strtoupper($this->data['captcha']);
                        if(($captchaSessao != $captchaUsuario) && (strlen($captchaSessao) > 4)) {
                            throw new Exception("Captcha inválido. Por favor, tente novamente.");
                        }

                        $nome = $this->data['FaleConosco']['nome'];
                        $email = $this->data['FaleConosco']['email'];
                        $assunto = $this->data['FaleConosco']['assunto'];
                        $mensagem = $this->data['FaleConosco']['mensagem'];

                        $Email = new CakeEmail('smtp');
                        $Email->template('fale_conosco');
                        $Email->emailFormat('html');
                        $Email->viewVars(array('nome' => $nome, 'email' => $email, 'assunto' => $assunto, 'mensagem' => $mensagem));
                        $Email->to(AppController::EMAIL_CONTATO);
                        $Email->subject('Portal - Fale Conosco');
                        $Email->send();
                        $this->Session->setFlash(__('Mensagem enviada com sucesso. Em breve entraremos em contato.'), 'sucesso');
                    } catch (SocketException $e){
                        $this->Session->setFlash(__("Não foi possível enviar o e-mail."), 'erro');
                    } catch (Exception $e){
                        $this->Session->setFlash(__($e->getMessage()), 'erro');
                    }
                }
            }
            
            $this->Session->delete("captcha");
            $this->redirect(array('action' => 'index'));
        }
        
        public function captcha() {
            $this->layout = "ajax";
            $this->set("image", $this->gerarImagemCaptcha());
        }

    }

?>