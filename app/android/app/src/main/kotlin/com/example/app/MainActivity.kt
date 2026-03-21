package com.example.app

import android.content.ActivityNotFoundException
import android.content.ContentValues
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import androidx.core.content.FileProvider
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodCall
import io.flutter.plugin.common.MethodChannel
import java.io.File
import java.io.FileInputStream
import java.io.IOException

class MainActivity : FlutterActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "com.example.app/manuals"
        ).setMethodCallHandler { call, result ->
            when (call.method) {
                "saveAndOpenPdf" -> {
                    try {
                        saveAndOpenPdf(call)
                        result.success(null)
                    } catch (e: Exception) {
                        result.error("PDF_ERROR", e.message, null)
                    }
                }

                else -> result.notImplemented()
            }
        }
    }

    private fun saveAndOpenPdf(call: MethodCall) {
        val sourcePath = call.argument<String>("sourcePath")
            ?: throw IllegalArgumentException("sourcePath is required")
        val fileName = call.argument<String>("fileName")
            ?: throw IllegalArgumentException("fileName is required")
        val subdirectory = call.argument<String>("subdirectory").orEmpty()

        val sourceFile = File(sourcePath)
        if (!sourceFile.exists()) {
            throw IOException("Source file not found: $sourcePath")
        }

        val targetFile = resolveTargetFile(fileName, subdirectory)
        val uri = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            savePdfWithMediaStore(sourceFile, fileName, subdirectory)
        } else {
            targetFile.parentFile?.mkdirs()
            sourceFile.copyTo(targetFile, overwrite = true)
            FileProvider.getUriForFile(
                this,
                "${applicationContext.packageName}.fileprovider",
                targetFile
            )
        }

        openPdf(uri)
    }

    private fun resolveTargetFile(fileName: String, subdirectory: String): File {
        val baseDirectory = Environment.getExternalStoragePublicDirectory(
            Environment.DIRECTORY_DOCUMENTS
        )
        val dir = if (subdirectory.isBlank()) {
            baseDirectory
        } else {
            File(baseDirectory, subdirectory)
        }
        return File(dir, fileName)
    }

    private fun savePdfWithMediaStore(sourceFile: File, fileName: String, subdirectory: String): Uri {
        val resolver = applicationContext.contentResolver
        val relativePath = buildString {
            append(Environment.DIRECTORY_DOCUMENTS)
            if (subdirectory.isNotBlank()) {
                append("/")
                append(subdirectory.trim('/'))
            }
        }

        findExistingPdf(fileName, relativePath)?.let { existingUri ->
            resolver.delete(existingUri, null, null)
        }

        val values = ContentValues().apply {
            put(MediaStore.Files.FileColumns.DISPLAY_NAME, fileName)
            put(MediaStore.Files.FileColumns.MIME_TYPE, "application/pdf")
            put(MediaStore.Files.FileColumns.RELATIVE_PATH, relativePath)
            put(MediaStore.Files.FileColumns.IS_PENDING, 1)
        }

        val collection = MediaStore.Files.getContentUri("external")
        val uri = resolver.insert(collection, values)
            ?: throw IOException("Could not create document entry for $fileName")

        try {
            FileInputStream(sourceFile).use { input ->
                resolver.openOutputStream(uri)?.use { output ->
                    input.copyTo(output)
                } ?: throw IOException("Could not open output stream for $fileName")
            }

            val completedValues = ContentValues().apply {
                put(MediaStore.Files.FileColumns.IS_PENDING, 0)
            }
            resolver.update(uri, completedValues, null, null)
            return uri
        } catch (e: Exception) {
            resolver.delete(uri, null, null)
            throw e
        }
    }

    private fun findExistingPdf(fileName: String, relativePath: String): Uri? {
        val resolver = applicationContext.contentResolver
        val selection = "${MediaStore.Files.FileColumns.RELATIVE_PATH} = ? AND " +
            "${MediaStore.Files.FileColumns.DISPLAY_NAME} = ?"
        val selectionArgs = arrayOf("$relativePath/", fileName)

        resolver.query(
            MediaStore.Files.getContentUri("external"),
            arrayOf(MediaStore.Files.FileColumns._ID),
            selection,
            selectionArgs,
            null
        )?.use { cursor ->
            if (cursor.moveToFirst()) {
                val id = cursor.getLong(0)
                return MediaStore.Files.getContentUri("external", id)
            }
        }

        return null
    }

    private fun openPdf(uri: Uri) {
        val intent = Intent(Intent.ACTION_VIEW).apply {
            setDataAndType(uri, "application/pdf")
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        }

        try {
            startActivity(intent)
        } catch (e: ActivityNotFoundException) {
            throw IllegalStateException("No hay una aplicacion disponible para abrir PDFs")
        }
    }
}
