<?php
    /**
     * @class homepage 
     * @author NHN (developers@xpressengine.com)
     * @brief  homepage package
     **/

    class homepage extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            $oModuleController = &getController('module');

            $oModuleController->insertTrigger('display', 'homepage', 'controller', 'triggerMemberMenu', 'before');
			$oModuleController->insertTrigger('moduleHandler.proc', 'homepage', 'controller', 'triggerApplyLayout', 'after');

			/* 신규 카페 생성 시작 2011 03 11*/
			// 패키지 설치시에만 샘플 카페 생성
            $args->site_srl = 0;
            $output = executeQuery('module.getSite', $args);
            if(!$output->data->index_module_srl) {
				// 카페 생성시 필요한 부수 정보(레이아웃, 위젯)이 없는경우는 샘플 카페 생성하지 않는다.
				if($this->_checkCreateSampleCafe()) $this->_createSampleCafe();
			}
			/* 신규 카페 생성 종료 2011 03 11*/

            return new Object();
        }

		function _checkCreateSampleCafe()
		{
			$layout = FileHandler::getRealPath('./layouts/xe_cafe');
			$login_info = FileHandler::getRealPath('./widgets/login_info/skins/default'); 
			$logged_members = FileHandler::getRealPath('./widgets/logged_members'); 
			$member_group = FileHandler::getRealPath('./widgets/member_group'); 
			$navigator = FileHandler::getRealPath('./widgets/navigator'); 
			$rank_count = FileHandler::getRealPath('./widgets/rank_count'); 
			$site_info = FileHandler::getRealPath('./widgets/site_info'); 

			return (is_dir($layout) && is_dir($login_info) && is_dir($logged_members) && is_dir($member_group) && is_dir($navigator) && is_dir($rank_count) && is_dir($site_info));
		}

		function _createSampleCafe()
		{
			// 카페레이아웃 생성
            $args->site_srl = 0;
            $args->layout_srl = getNextSequence();
            $args->layout = 'xe_cafe';
            $args->title = 'CafeXE default layout';
			$args->layout_type = "P";

            // DB 입력
            $oLayoutAdminController = &getAdminController('layout');
            $output = $oLayoutAdminController->insertLayout($args);
            if(!$output->toBool()) return $output;

            $oLayoutAdminController->initLayout($args->layout_srl, $args->layout);

			$layout_srl = $args->layout_srl;

			unset($args);

            // 결과 리턴
            $oHomepageAdminController = &getAdminController('homepage');
            $oHomepageModel = &getModel('homepage');
            $oModuleModel = &getModel('module');
            $oMemberModel = &getModel('member');
            $oMemberController = &getController('member');

            $cafe_id = 'sample_cafe';
            $cafe_title = 'CafeXE 샘플 카페';
            $cafe_description = '샘플로 설치된 카페입니다.';

            $domain = $cafe_id;

            $oHomepageAdminController->insertHomepage($cafe_title, $domain);
            if(!$oHomepageAdminController->toBool()) return $output;

            $site_srl = $oHomepageAdminController->get('site_srl');

            // 홈페이지 제목/내용 변경
            $homepage_info = $oHomepageModel->getHomepageInfo($site_srl);
            $args->title = $cafe_title;
            $args->description = $cafe_description;
            $args->layout_srl = $layout_srl;
            $args->site_srl = $site_srl;
            $output = executeQuery('homepage.updateHomepage', $args);
            if(!$output->toBool()) return $output;

            // 현재 사용자 가입 및 관리자 주기
            $logged_info = Context::get('logged_info');

            $default_group = $oMemberModel->getDefaultGroup($site_srl);
            $oMemberController->addMemberToGroup($logged_info->member_srl, $default_group->group_srl, $site_srl);

            $oModuleController = &getController('module');
            $output = $oModuleController->insertSiteAdmin($site_srl, array($logged_info->user_id));
		}


        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');
            $oDB = &DB::getInstance();

            // 2009. 02. 11 가상 사이트의 로그인 정보 영역에 관리 기능이 추가되어 표시되도록 트리거 등록
            if(!$oModuleModel->getTrigger('display', 'homepage', 'controller', 'triggerMemberMenu', 'before')) return true;

            // 2009. 04. 23 카페의 설명
            if(!$oDB->isColumnExists("homepages","description")) return true;

            if(!$oModuleModel->getTrigger('moduleHandler.proc', 'homepage', 'controller', 'triggerApplyLayout', 'after')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');
            $oDB = &DB::getInstance();

            // 2009. 02. 11 가상 사이트의 로그인 정보 영역에 관리 기능이 추가되어 표시되도록 트리거 등록
            if(!$oModuleModel->getTrigger('display', 'homepage', 'controller', 'triggerMemberMenu', 'before')) 
                $oModuleController->insertTrigger('display', 'homepage', 'controller', 'triggerMemberMenu', 'before');

            // 2009. 04. 23 카페의 설명
            if(!$oDB->isColumnExists("homepages","description")) 
                $oDB->addColumn("homepages","description","text");

            if(!$oModuleModel->getTrigger('moduleHandler.proc', 'homepage', 'controller', 'triggerApplyLayout', 'after') )
                $oModuleController->insertTrigger('moduleHandler.proc', 'homepage', 'controller', 'triggerApplyLayout', 'after');


            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
