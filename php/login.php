<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *                                   ATTENTION!
 * If you see this message in your browser (Internet Explorer, Mozilla Firefox, Google Chrome, etc.)
 * this means that PHP is not properly installed on your web server. Please refer to the PHP manual
 * for more details: http://php.net/manual/install.php 
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

include_once dirname(__FILE__) . '/' . 'components/utils/check_utils.php';
CheckPHPVersion();
CheckTemplatesCacheFolderIsExistsAndWritable();

include_once dirname(__FILE__) . '/' . 'phpgen_settings.php';
include_once dirname(__FILE__) . '/' . 'components/page.php';
include_once dirname(__FILE__) . '/' . 'components/renderers/renderer.php';
include_once dirname(__FILE__) . '/' . 'components/renderers/list_renderer.php';
include_once dirname(__FILE__) . '/' . 'authorization.php';
include_once dirname(__FILE__) . '/' . 'database_engine/sqlite_engine.php';
include_once dirname(__FILE__) . '/' . 'components/security/user_identity_storage/user_identity_session_storage.php';

function GetConnectionOptions() {
    $result = GetGlobalConnectionOptions();
    $result['client_encoding'] = '';
    return $result;
}

class LoginControl {
    /** @var IdentityCheckStrategy */
    private $identityCheckStrategy;
    private $urlToRedirectAfterLogin;
    private $errorMessage;
    private $lastUserName;
    private $lastSaveidentity;
    private $loginAsGuestLink;
    /** @var \Captions */
    private $captions;

    /**
     * @var UserIdentityCookieStorage
     */
    private $userIdentityStorage;

    #region Events
    public $OnGetCustomTemplate;

    #endregion

    public function __construct(
        $identityCheckStrategy,
        $urlToRedirectAfterLogin,
        Captions $captions,
        UserIdentityStorage $userIdentityStorage) {
        $this->identityCheckStrategy = $identityCheckStrategy;
        $this->urlToRedirectAfterLogin = $urlToRedirectAfterLogin;
        $this->errorMessage = '';
        $this->captions = $captions;
        $this->lastSaveidentity = false;
        $this->userIdentityStorage = $userIdentityStorage;
        $this->OnGetCustomTemplate = new Event();
    }

    public function Accept(Renderer $renderer) {
        $renderer->RenderLoginControl($this);
    }

    public function GetErrorMessage() {
        return $this->errorMessage;
    }

    public function GetLastUserName() {
        return $this->lastUserName;
    }

    public function GetLastSaveidentity() {
        return $this->lastSaveidentity;
    }

    public function CanLoginAsGuest() {
        return false;
    }

    public function GetLoginAsGuestLink() {
        $pageInfos = GetPageInfos();
        foreach ($pageInfos as $pageInfo) {
            if (GetApplication()->GetUserRoles('guest', $pageInfo['name'])->HasViewGrant()) {
                return $pageInfo['filename'];
            }
        }
        return $this->urlToRedirectAfterLogin;
    }

    public function CheckUsernameAndPassword($username, $password, &$errorMessage) {
        try {
            $result = $this->identityCheckStrategy->CheckUsernameAndPassword($username, $password, $errorMessage);
            if (!$result) {
                $errorMessage = $this->captions->GetMessageString('UsernamePasswordWasInvalid');
            }
            return $result;
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            return false;
        }
    }

    public function SaveUserIdentity($username, $password, $saveidentity) {
        $this->userIdentityStorage->SaveUserIdentity(new UserIdentity($username, $password, $saveidentity));
    }

    public function ClearUserIdentity() {
        $this->userIdentityStorage->ClearUserIdentity();
    }

    private function DoOnAfterLogin($userName) {
        $connectionFactory = new SqlitePDOConnectionFactory();
        $connection = $connectionFactory->CreateConnection(GetConnectionOptions());
        try {
            $connection->Connect();
        } catch (Exception $e) {
            ShowErrorPage($e->getMessage());
            die;
        }

        $this->OnAfterLogin($userName, $connection);

        $connection->Disconnect();
    }

    private function OnAfterLogin($userName, $connection) {

    }

    private function GetUrlToRedirectAfterLogin() {
        if (GetApplication()->GetSuperGlobals()->IsGetValueSet('redirect')) {
            return GetApplication()->GetSuperGlobals()->GetGetValue('redirect');
        }

        $pageInfos = GetPageInfos();
        foreach ($pageInfos as $pageInfo) {
            if (GetCurrentUserGrantForDataSource($pageInfo['name'])->HasViewGrant()) {
                return $pageInfo['filename'];
            }
        }
        return $this->urlToRedirectAfterLogin;
    }

    public function ProcessMessages() {
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];
            $saveidentity = isset($_POST['saveidentity']);

            if ($this->CheckUsernameAndPassword($username, $password, $this->errorMessage)) {
                $this->SaveUserIdentity($username, $password, $saveidentity);
                $this->DoOnAfterLogin($username);
                header('Location: ' . $this->GetUrlToRedirectAfterLogin());
                exit;
            } else {
                $this->lastUserName = $username;
                $this->lastSaveidentity = $saveidentity;
            }
        } elseif (isset($_GET[OPERATION_PARAMNAME]) && $_GET[OPERATION_PARAMNAME] == 'logout') {
            $this->ClearUserIdentity();
        }
    }

    public function GetCustomTemplate($part, $defaultValue, &$params = null) {
        $result = null;

        if (!$params)
            $params = array();

        $this->OnGetCustomTemplate->Fire(array($part, null, &$result, &$params));
        if ($result)
            return Path::Combine('custom_templates', $result);
        else
            return $defaultValue;
    }
}

class LoginPage extends CustomLoginPage {
    private $loginControl;
    private $renderer;
    private $header;
    private $footer;

    private $captions;

    public function __construct(LoginControl $loginControl) {
        parent::__construct();
        $this->loginControl = $loginControl;
        $this->captions = GetCaptions('UTF-8');
        $this->renderer = new ViewAllRenderer($this->captions);
    }

    public function GetLoginControl() {
        return $this->loginControl;
    }

    public function Accept(Renderer $renderer) {
        $renderer->RenderLoginPage($this);
    }

    public function GetContentEncoding() {
        return 'UTF-8';
    }

    public function GetCaption() {
        return 'Login';
    }

    public function SetHeader($value) {
        $this->header = $value;
    }

    public function GetHeader() {
        return $this->RenderText($this->header);
    }

    public function SetFooter($value) {
        $this->footer = $value;
    }

    public function GetFooter() {
        return $this->RenderText($this->footer);
    }

    public function BeginRender() {
        $this->loginControl->ProcessMessages();
    }

    public function EndRender() {
        echo $this->renderer->Render($this);
    }

    public function addListeners() {
        $this->OnGetCustomTemplate->AddListener('Global_GetCustomTemplateHandler');
        $this->loginControl->OnGetCustomTemplate->AddListener('Global_GetCustomTemplateHandler');
    }
}


// to start session
GetApplication();

$identityCheckStrategy = GetIdentityCheckStrategy();
$userIdentityStorage = new UserIdentitySessionStorage($identityCheckStrategy);

$loginPage = new LoginPage(
    new LoginControl(
        $identityCheckStrategy,
        'jacks.php',
        GetCaptions('UTF-8'),
        $userIdentityStorage
    )
);

SetUpUserAuthorization();

$loginPage->addListeners();

$loginPage->SetHeader(GetPagesHeader());
$loginPage->SetFooter(GetPagesFooter());
$loginPage->BeginRender();
$loginPage->EndRender();
