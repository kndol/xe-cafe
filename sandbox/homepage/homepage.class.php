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
			// 카페레이아웃 생성
            $args->site_srl = 0;
            $args->layout_srl = getNextSequence();
            $args->layout = 'xe_cafe';
            $args->title = 'CafeXE default layout';
			$args->layout_type = "P";

            // DB 입력
            $output = $this->insertLayout($args);
            if(!$output->toBool()) return $output;

            $oLayoutAdminController = &getAdminController('layout');
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
            $cafe_title = 'sample cafe입니다.';
            $cafe_description = 'Cafe XE 샘플 입니다.';

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

            $output = $oModuleController->insertSiteAdmin($site_srl, array($logged_info->user_id));

            return new Object();
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
