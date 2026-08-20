<?php
namespace Institution\Model\Traits;

trait RouteInstitutionIdTrait
{
    /**
     * Decode the ':institutionId' route param (see plugins/Institution/config/routes.php '/:institutionId/:action/*'),
     * encoded as paramsEncode(['id' => $institutionId]) - e.g. InstitutionsController::Associations() add/edit links.
     *
     * @param \Cake\Http\ServerRequest|null $request Request carrying the 'institutionId' route param.
     * @param object $decoder Any object exposing paramsDecode() (a Controller or Table using SecurityTrait).
     * @return mixed institution id if resolved, null otherwise.
     */
    protected function resolveRouteInstitutionId($request, $decoder)
    {
        if (empty($request)) {
            return null;
        }

        $routeInstitutionId = $request->getParam('institutionId');
        if (empty($routeInstitutionId)) {
            return null;
        }

        try {
            $decoded = $decoder->paramsDecode($routeInstitutionId);
        } catch (\Exception $e) {
            return null;
        }

        if (!empty($decoded['id'])) {
            return $decoded['id'];
        }

        if (!empty($decoded['institution_id'])) {
            return $decoded['institution_id'];
        }

        return null;
    }
}
