<?php

namespace App\Reporting\Contracts;

enum ReportType: string
{
    case DailySalesReport = 'daily_sales_report';
    case ProductMetric = 'product_metric';
    case LogEvent = 'log_event';
}
