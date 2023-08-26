<?php

namespace App\Enums;

enum DeploymentStatusEnum : string
{
    case Awaiting = 'awaiting';
    case RunningDeployment = 'running_deployment';
    case DeploymentError = 'deployment_error';
    case OnDevelopment = 'on_development';
    case Testing = 'testing';
    case TestError = 'test_error';
    case ProductionDeploymentAwaiting = 'production_awaiting';
    case OnProduction = 'on_production';
}
