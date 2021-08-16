<?php

    class ProfissionalSaudeController extends AppController {
        
        var $name = 'ProfissionalSaude';
        public $uses = array('Noticia', 'Banner');
        
        function beforeFilter(){
            parent::beforeFilter();
            $this->Auth->allow('index', 'carregarEventosCapacitacoes');
            $this->set('area', parent::PROFISSIONAL_SAUDE);
        } 
        
        public function index() {
            
            $this->set('noticias', parent::carregarNoticiasPorArea(parent::PROFISSIONAL_SAUDE));
            $this->set('outrasNoticias', parent::carregarNoticiasSemImagemPorArea(parent::PROFISSIONAL_SAUDE));
            $this->set('noticiasGestaoPessoas', parent::carregarNoticiasPorVocabulario(parent::GESTAO_PESSOAS));
            $this->set('noticiasSaudeTrabalhador' , parent::carregarNoticiasPorVocabulario(parent::SAUDE_TRABALHADOR));
            $this->set('noticiasTelessaude', parent::carregarNoticiasPorVocabulario(parent::TELESSAUDE));
            $this->set('noticiasCienciaTecnologia', parent::carregarNoticiasPorVocabulario(parent::CIENCIA_TECNOLOGIA));
            $this->set('notasMidia', parent::carregarNotasMidiasPorArea(parent::PROFISSIONAL_SAUDE));
            $this->set('banner', parent::carregarBannersPorAreaOuTodos(parent::PROFISSIONAL_SAUDE));
            $this->set('legislacoes', $this->carregarLegislacoesPorArea(parent::PROFISSIONAL_SAUDE));

            parent::carregarDadosBarraProfissional(parent::PROFISSIONAL_SAUDE);
        }
        
        public function carregarEventosCapacitacoes($codigoArea = null) {
            $this->layout = 'ajax';

            $this->set('eventos', parent::carregarEventosPorArea($codigoArea));
            $this->set('capacitacoes', parent::carregarCapacitacoes());
        }
    }

?>