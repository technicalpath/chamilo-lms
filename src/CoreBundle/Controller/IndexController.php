<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

//use Chamilo\CoreBundle\Admin\CourseAdmin;
use Chamilo\CoreBundle\Framework\PageController;
use Chamilo\PageBundle\Entity\Block;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Class IndexController
 * author Julio Montoya <gugli100@gmail.com>.
 *
 * @package Chamilo\CoreBundle\Controller
 */
class IndexController extends BaseController
{
    /**
     * The Chamilo index home page.
     *
     * @Route("/", name="home", methods={"GET", "POST"}, options={"expose"=true})
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $pageController = new PageController();
        $user = $this->getUser();
        $userId = 0;
        if ($user) {
            $userId = $this->getUser()->getId();
        }

        return $this->render(
            '@ChamiloCore/Index/index.html.twig',
            [
                'content' => ''
                //'home_page_block' => $pageController->returnHomePage()
            ]
        );
    }

    /**
     * Toggle the student view action.
     *
     * @Route("/toggle_student_view", methods={"GET"})
     *
     * @Security("has_role('ROLE_TEACHER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function toggleStudentViewAction(Request $request): Response
    {
        if (!api_is_allowed_to_edit(false, false, false, false)) {
            return '';
        }
        $studentView = $request->getSession()->get('studentview');
        if (empty($studentView) || $studentView === 'studentview') {
            $request->getSession()->set('studentview', 'teacherview');

            return 'teacherview';
        } else {
            $request->getSession()->set('studentview', 'studentview');

            return 'studentview';
        }
    }
}
