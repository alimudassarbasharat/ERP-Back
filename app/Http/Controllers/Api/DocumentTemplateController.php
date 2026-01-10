<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FeeInvoice;
use App\Models\Challan;
use App\Jobs\GenerateInvoicePdfJob;
use App\Jobs\GenerateChallanPdfJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Validator;

class DocumentTemplateController extends Controller
{
    /**
     * Generate PDF for a single invoice (async)
     */
    public function generateInvoicePdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:fee_invoices,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoice = FeeInvoice::find($request->invoice_id);

        if ($invoice->pdf_status === 'generating') {
            return response()->json([
                'success' => false,
                'message' => 'PDF is already being generated',
            ], 400);
        }

        // Dispatch job immediately (async)
        GenerateInvoicePdfJob::dispatch($invoice->id);

        return response()->json([
            'success' => true,
            'message' => 'PDF generation job dispatched',
            'data' => [
                'invoice_id' => $invoice->id,
                'status' => 'pending',
            ],
        ], 202);
    }

    /**
     * Generate PDFs for multiple invoices (bulk, async)
     */
    public function generateBulkInvoicePdfs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'exists:fee_invoices,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoiceIds = $request->invoice_ids;

        // Create batch of jobs
        $jobs = collect($invoiceIds)->map(function ($invoiceId) {
            return new GenerateInvoicePdfJob($invoiceId);
        })->toArray();

        $batch = Bus::batch($jobs)->dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Bulk PDF generation jobs dispatched',
            'data' => [
                'batch_id' => $batch->id,
                'total_jobs' => count($invoiceIds),
                'status' => 'pending',
            ],
        ], 202);
    }

    /**
     * Generate PDF for a single challan (async)
     */
    public function generateChallanPdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'challan_id' => 'required|exists:challans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $challan = Challan::find($request->challan_id);

        if ($challan->pdf_status === 'generating') {
            return response()->json([
                'success' => false,
                'message' => 'PDF is already being generated',
            ], 400);
        }

        // Dispatch job immediately (async)
        GenerateChallanPdfJob::dispatch($challan->id);

        return response()->json([
            'success' => true,
            'message' => 'PDF generation job dispatched',
            'data' => [
                'challan_id' => $challan->id,
                'status' => 'pending',
            ],
        ], 202);
    }

    /**
     * Generate PDFs for multiple challans (bulk, async)
     */
    public function generateBulkChallanPdfs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'challan_ids' => 'required|array',
            'challan_ids.*' => 'exists:challans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $challanIds = $request->challan_ids;

        // Create batch of jobs
        $jobs = collect($challanIds)->map(function ($challanId) {
            return new GenerateChallanPdfJob($challanId);
        })->toArray();

        $batch = Bus::batch($jobs)->dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Bulk PDF generation jobs dispatched',
            'data' => [
                'batch_id' => $batch->id,
                'total_jobs' => count($challanIds),
                'status' => 'pending',
            ],
        ], 202);
    }

    /**
     * Check PDF generation status
     */
    public function checkInvoicePdfStatus(Request $request, $invoiceId)
    {
        $invoice = FeeInvoice::find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_id' => $invoice->id,
                'pdf_status' => $invoice->pdf_status,
                'pdf_path' => $invoice->pdf_path,
                'pdf_generated_at' => $invoice->pdf_generated_at,
            ],
        ]);
    }

    /**
     * Check challan PDF generation status
     */
    public function checkChallanPdfStatus(Request $request, $challanId)
    {
        $challan = Challan::find($challanId);

        if (!$challan) {
            return response()->json([
                'success' => false,
                'message' => 'Challan not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'challan_id' => $challan->id,
                'pdf_status' => $challan->pdf_status,
                'pdf_path' => $challan->pdf_path,
                'pdf_generated_at' => $challan->pdf_generated_at,
            ],
        ]);
    }
}
