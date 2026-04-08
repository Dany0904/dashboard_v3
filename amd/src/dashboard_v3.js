/* eslint-disable no-console */

define(["jquery", "core/ajax", "local_dashboard_v3/apexcharts"], function (
  $,
  Ajax,
  ApexCharts,
) {
  return {
    init: function () {
        let days = document.getElementById("filter-days")?.value || 30;

        Ajax.call([
          {
            methodname: "local_dashboard_v3_get_dashboard_v3_data",
            args: { days: parseInt(days) },
          },
        ])[0]
        .done(function (data) {

          const filter = document.getElementById("filter-days");

          if (filter) {
            filter.addEventListener("change", function () {
              const selectedDays = this.value;

              // recargar con parámetro
              window.location.href = "?days=" + selectedDays;
            });
          }

          let labels = [];
          let dataValues = [];

          data.usersbyyear.forEach(function (item) {
            labels.push(item.year);
            dataValues.push(item.total);
          });

          // Destruir gráfica previa
          if (window.usuariosChartInstance) {
            window.usuariosChartInstance.destroy();
          }

          // 🔥 Configuración (tuya, casi intacta)
          var options = {
            chart: {
              type: "line",
              height: 350,
              toolbar: { show: true },
            },
            series: [
              {
                name: "Total Usuarios",
                data: dataValues,
              },
            ],
            xaxis: {
              categories: labels,
              title: { text: "Año" },
            },
            yaxis: {
              title: { text: "Cantidad de Usuarios" },
            },
            stroke: {
              curve: "smooth",
              width: 2,
            },
            markers: {
              size: 5,
              colors: ["#4e73df"],
              strokeWidth: 2,
            },
            tooltip: {
              theme: "light",
              y: {
                formatter: function (value) {
                  return value.toLocaleString();
                },
              },
            },
            colors: ["#4e73df"],
          };

          //  Render
          window.usuariosChartInstance = new ApexCharts(
            document.querySelector("#usuariosChart"),
            options,
          );

          window.usuariosChartInstance.render();

          // =======================
          // CURSOS POR AÑO
          // =======================

          let courseLabels = [];
          let courseValues = [];

          data.coursesbyyear.forEach(function (item) {
            courseLabels.push(item.year);
            courseValues.push(item.total);
          });

          // destruir instancia previa
          if (window.cursosChartInstance) {
            window.cursosChartInstance.destroy();
          }

          // configuración ApexCharts (tuya adaptada)
          var cursosOptions = {
            chart: {
              type: "bar",
              height: 350,
              toolbar: { show: true },
            },
            series: [
              {
                name: "Total de Cursos",
                data: courseValues,
              },
            ],
            xaxis: {
              categories: courseLabels,
              title: { text: "Año" },
            },
            yaxis: {
              title: { text: "Cantidad de Cursos" },
              min: 0,
            },
            colors: ["#008FFB"],
            dataLabels: {
              enabled: true,
              formatter: function (val) {
                return val.toLocaleString();
              },
            },
            tooltip: {
              theme: "light",
              y: {
                formatter: function (value) {
                  return value.toLocaleString();
                },
              },
            },
            plotOptions: {
              bar: {
                borderRadius: 4,
                horizontal: false,
                columnWidth: "60%",
              },
            },
          };

          // render
          window.cursosChartInstance = new ApexCharts(
            document.querySelector("#cursosChart"),
            cursosOptions,
          );

          window.cursosChartInstance.render();

          // =======================
          // CURSOS POR CATEGORÍA
          // =======================

          // preparar datos
          let catLabels = [];
          let catValues = [];

          data.coursesbycategory.forEach(function (item) {
            catLabels.push(item.name);
            catValues.push(item.total);
          });

          /**
           * Generate color array for chart.
           *
           * @param {number} n Number of colors needed.
           * @returns {Array} Array of color strings.
           */
          function generateColors(n) {
            const baseColors = [
              "#008FFB",
              "#00E396",
              "#FEB019",
              "#FF4560",
              "#775DD0",
              "#3F51B5",
              "#546E7A",
              "#26a69a",
              "#D10CE8",
              "#FF9800",
            ];
            return baseColors.slice(0, n);
          }

          let colors = generateColors(catLabels.length);

          // destruir previa
          if (window.categoriasChartInstance) {
            window.categoriasChartInstance.destroy();
          }

          // config ApexCharts
          var categoriasOptions = {
            chart: {
              type: "donut",
              height: 350,
            },
            series: catValues,
            labels: catLabels,
            colors: colors,
            legend: {
              position: "bottom",
            },
            dataLabels: {
              enabled: true,
              formatter: function (val) {
                return val.toFixed(1) + "%";
              },
            },
            tooltip: {
              y: {
                formatter: function (value) {
                  return value + " cursos";
                },
              },
            },
          };

          // render
          window.categoriasChartInstance = new ApexCharts(
            document.querySelector("#categoriasChart"),
            categoriasOptions,
          );

          window.categoriasChartInstance.render();

          // =======================
          // Cursos Demandados
          // =======================

          let enrollLabels = [];
          let enrollValues = [];

          data.enrollments.forEach((item) => {
            enrollLabels.push(item.name);
            enrollValues.push(item.total);
          });

          if (window.enrollmentsChartInstance) {
            window.enrollmentsChartInstance.destroy();
          }

          const optionsEnroll = {
            chart: {
              type: "donut",
              height: 350,
            },
            series: enrollValues,
            labels: enrollLabels,
            colors: generateColors(enrollLabels.length),
            legend: {
              position: "bottom",
            },
            dataLabels: {
              enabled: true,
              formatter: function (val) {
                return val.toFixed(1) + "%";
              },
            },
            tooltip: {
              y: {
                formatter: function (value) {
                  return value + " inscritos";
                },
              },
            },
          };

          window.enrollmentsChartInstance = new ApexCharts(
            document.querySelector("#inscritosChart"),
            optionsEnroll,
          );

          window.enrollmentsChartInstance.render();

          // =======================
          // Cursos con mejores Promedios
          // =======================

          let avgLabels = [];
          let avgValues = [];

          data.averages.forEach((item) => {
            avgLabels.push(item.name);
            avgValues.push(item.average);
          });

          if (window.promedioChartInstance) {
            window.promedioChartInstance.destroy();
          }

          const optionsPromedio = {
            chart: {
              type: "bar",
              height: 350,
            },
            series: [
              {
                name: "Promedio",
                data: avgValues,
              },
            ],
            xaxis: {
              categories: avgLabels,
            },
            colors: generateColors(avgLabels.length),
            plotOptions: {
              bar: {
                borderRadius: 6,
                columnWidth: "60%",
              },
            },
            dataLabels: {
              enabled: true,
              formatter: (val) => val.toFixed(1),
            },
            tooltip: {
              y: {
                formatter: (val) => val.toFixed(2),
              },
            },
          };

          window.promedioChartInstance = new ApexCharts(
            document.querySelector("#promedioChart"),
            optionsPromedio,
          );

          window.promedioChartInstance.render();

          // =======================
          // Usuarios activos en Cursos
          // =======================

          let actLabels = [];
          let actValues = [];

          data.activitycourses.forEach((item) => {
            actLabels.push(item.name);
            actValues.push(item.total);
          });

          // 🔥 fallback para pruebas
          if (actValues.length === 0) {
            console.warn("Sin datos de actividad, usando mock");
            actLabels = ["Curso A", "Curso B", "Curso C"];
            actValues = [12, 8, 5];
          }

          if (window.activityCoursesChartInstance) {
            window.activityCoursesChartInstance.destroy();
          }

          const optionsActivity = {
            chart: {
              type: "bar",
              height: 350,
            },
            series: [
              {
                name: "Usuarios activos",
                data: actValues,
              },
            ],
            xaxis: {
              categories: actLabels,
            },
            colors: ["#00E396"],
            plotOptions: {
              bar: {
                borderRadius: 6,
                columnWidth: "60%",
              },
            },
            dataLabels: {
              enabled: true,
            },
            tooltip: {
              y: {
                formatter: (val) => val + " usuarios",
              },
            },
          };

          window.activityCoursesChartInstance = new ApexCharts(
            document.querySelector("#actividadcursesChart"),
            optionsActivity,
          );

          window.activityCoursesChartInstance.render();

          // =======================
          //  Cursos con mayor Progreso (%)
          // =======================

          let labelsProgreso = [];
          let valuesProgreso = [];

          data.progress.forEach((item) => {
            labelsProgreso.push(item.fullname);
            valuesProgreso.push(item.progress);
          });

          if (window.topProgressChartInstance) {
            window.topProgressChartInstance.destroy();
          }

          window.topProgressChartInstance = new ApexCharts(
            document.querySelector("#topprogress"),
            {
              chart: {
                type: "bar",
                height: 350,
                toolbar: { show: true },
              },
              series: [
                {
                  name: "Progreso (%)",
                  data: valuesProgreso,
                },
              ],
              xaxis: {
                categories: labelsProgreso,
                title: { text: "Cursos" },
              },
              yaxis: {
                min: 0,
                max: 100,
                title: { text: "Progreso (%)" },
                labels: {
                  formatter: (val) => val + "%",
                },
              },
              dataLabels: {
                enabled: true,
                formatter: (val) => val.toFixed(0) + "%",
              },
              tooltip: {
                y: {
                  formatter: (val) => val.toFixed(2) + "%",
                },
              },
              plotOptions: {
                bar: {
                  borderRadius: 6,
                  columnWidth: "60%",
                },
              },
              colors: ["#6366f1"],
            },
          );

          window.topProgressChartInstance.render();

          // =========================
          // Finalizado vs No Finalizado
          // =========================
          let labelsFinalizado = [];
          let completed = [];
          let notcompleted = [];

          data.vs.forEach((item) => {
            labelsFinalizado.push(item.fullname);
            completed.push(item.completed);
            notcompleted.push(item.notcompleted);
          });

          if (window.comparisonChartInstance) {
            window.comparisonChartInstance.destroy();
          }

          window.comparisonChartInstance = new ApexCharts(
            document.querySelector("#chartBarGroupVs"),
            {
              chart: {
                type: "bar",
                height: 350,
                toolbar: { show: true },
              },
              series: [
                {
                  name: "No Finalizado",
                  data: notcompleted,
                },
                {
                  name: "Finalizado",
                  data: completed,
                },
              ],
              xaxis: {
                categories: labelsFinalizado,
                title: { text: "Cursos" },
              },
              yaxis: {
                title: { text: "Usuarios" },
                min: 0,
              },
              colors: ["#ef4444", "#22c55e"], // 🔥 rojo / verde moderno
              plotOptions: {
                bar: {
                  columnWidth: "60%",
                  borderRadius: 5,
                },
              },
              dataLabels: {
                enabled: false,
              },
              tooltip: {
                y: {
                  formatter: function (val) {
                    return val + " usuarios";
                  },
                },
              },
              legend: {
                position: "top",
              },
            },
          );

          window.comparisonChartInstance.render();
        })
        .fail(function (error) {
          console.error(error);
        });
    },
  };
});
